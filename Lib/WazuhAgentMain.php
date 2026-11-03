<?php

declare(strict_types=1);

/*
 * MikoPBX - free phone system for small business
 * Copyright © 2017-2026 Alexey Portnov and Nikolay Beketov
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\ModuleWazuhAgent\Lib;

use MikoPBX\Core\System\Processes;
use MikoPBX\Core\System\SystemMessages;
use MikoPBX\Core\System\Util;
use MikoPBX\Modules\PbxExtensionUtils;
use Modules\ModuleWazuhAgent\Models\ModuleWazuhAgent;
use RuntimeException;

/**
 * Installs, configures, starts and stops the official Wazuh agent on the station.
 *
 * The station has no package database, so the agent inventory is built from the image
 * manifest /offload/package-manifest.tsv (Core#1365) as a dpkg status file.
 */
class WazuhAgentMain
{
    public const string MODULE_ID = 'ModuleWazuhAgent';

    // ponytail: x86_64 only and pinned to one upstream deb; ARM64 and CI-built binaries in ModuleWazuhAgent#1 stage 2.
    public const string AGENT_VERSION = '4.14.9-1';
    private const string DEB_URL = 'https://packages.wazuh.com/4.x/apt/pool/main/w/wazuh-agent/wazuh-agent_4.14.9-1_amd64.deb';
    private const string DEB_SHA256 = '20178fe13fa8fa4742dd3db3a19efaeed15faa79749fc47f606ca61a7ccad1f4';

    private const string LINK = '/var/ossec';
    private const string MANIFEST = '/offload/package-manifest.tsv';
    private const string DPKG_DIR = '/var/lib/dpkg';
    private const string USER = 'wazuh';

    /** Started in this order, stopped in reverse. */
    private const array DAEMONS = ['wazuh-agentd', 'wazuh-modulesd'];

    public static function moduleDir(): string
    {
        return PbxExtensionUtils::getModuleDir(self::MODULE_ID);
    }

    public static function agentHome(): string
    {
        return self::moduleDir() . '/agent';
    }

    /** Persistent copy of client.keys: db/ survives module updates (PbxExtensionSetupBase backup). */
    private static function savedKeysPath(): string
    {
        return self::moduleDir() . '/db/client.keys';
    }

    private static function agentIdPath(): string
    {
        return self::moduleDir() . '/db/agent-id';
    }

    public static function isInstalled(): bool
    {
        return is_file(self::agentHome() . '/bin/wazuh-agentd');
    }

    /**
     * Downloads the official deb, verifies it and unpacks var/ossec into agent/.
     *
     * @throws RuntimeException on download, checksum or unpack failure
     */
    public static function installAgent(): void
    {
        $tmp = self::moduleDir() . '/tmp-install';
        self::run('rm -rf ' . escapeshellarg($tmp));
        Util::mwMkdir($tmp);
        try {
            $deb = "$tmp/agent.deb";
            $curl = Util::which('curl');
            if (self::run("$curl -fsSL --max-time 300 -o " . escapeshellarg($deb) . ' ' . escapeshellarg(self::DEB_URL)) !== 0) {
                throw new RuntimeException('Download of ' . self::DEB_URL . ' failed');
            }
            if (hash_file('sha256', $deb) !== self::DEB_SHA256) {
                throw new RuntimeException('Checksum mismatch for ' . self::DEB_URL);
            }
            $data = self::extractDebData($deb, $tmp);
            $tar = Util::which('tar');
            $cmd = "$tar -xf " . escapeshellarg($data) . ' -C ' . escapeshellarg($tmp)
                . ' --exclude=./var/ossec/packages_files ./var/ossec';
            if (self::run($cmd) !== 0 || !is_file("$tmp/var/ossec/bin/wazuh-agentd")) {
                throw new RuntimeException('Unpacking the agent failed');
            }
            self::run('rm -rf ' . escapeshellarg(self::agentHome()));
            if (!rename("$tmp/var/ossec", self::agentHome())) {
                throw new RuntimeException('Cannot move the agent into ' . self::agentHome());
            }
        } finally {
            self::run('rm -rf ' . escapeshellarg($tmp));
        }
    }

    /**
     * Copies data.tar.* out of a deb (an ar archive); the station has no `ar`.
     */
    private static function extractDebData(string $deb, string $dir): string
    {
        $in = fopen($deb, 'rb');
        if ($in === false || fread($in, 8) !== "!<arch>\n") {
            throw new RuntimeException('Not a deb archive');
        }
        try {
            while (($header = fread($in, 60)) !== false && strlen($header) === 60) {
                $name = rtrim(substr($header, 0, 16), " /");
                $size = (int)trim(substr($header, 48, 10));
                if (str_starts_with($name, 'data.tar')) {
                    $path = "$dir/$name";
                    $out = fopen($path, 'wb');
                    if ($out === false || stream_copy_to_stream($in, $out, $size) !== $size) {
                        throw new RuntimeException('Cannot extract data archive');
                    }
                    fclose($out);
                    return $path;
                }
                fseek($in, $size + ($size % 2), SEEK_CUR);
            }
        } finally {
            fclose($in);
        }
        throw new RuntimeException('No data archive in deb');
    }

    public static function getSettings(): ?ModuleWazuhAgent
    {
        return ModuleWazuhAgent::findFirst() ?: null;
    }

    public static function startService(): void
    {
        $settings = self::getSettings();
        if ($settings === null || trim((string)$settings->managerAddress) === '') {
            return;
        }
        if (!self::isInstalled()) {
            SystemMessages::sysLogMsg(self::MODULE_ID, 'Agent files are missing, reinstall the module');
            return;
        }
        if (self::runningPids() !== []) {
            // #7: enrollment is not awaited at start; pick up the key on the next call (every Asterisk restart).
            self::saveKeys();
            return;
        }
        self::prepareSystem();
        self::writeInventory();
        self::forgetKeysIfEnrollmentChanged($settings);
        self::writeConfig($settings);
        self::restoreKeys();
        self::fixRights();

        $home = self::agentHome();
        foreach (self::DAEMONS as $daemon) {
            self::run("$home/bin/$daemon");
        }
    }

    public static function stopService(): void
    {
        foreach (array_reverse(self::DAEMONS) as $daemon) {
            $pid = self::pidOf($daemon);
            if ($pid === '') {
                continue;
            }
            self::run("kill $pid");
            for ($i = 0; $i < 20 && self::pidOf($daemon) !== ''; $i++) {
                usleep(250000);
            }
            $pid = self::pidOf($daemon);
            if ($pid !== '') {
                self::run("kill -9 $pid");
            }
        }
        self::saveKeys();
    }

    public static function restartService(): void
    {
        self::stopService();
        self::startService();
    }

    /**
     * Removes everything the agent created outside the module directory.
     */
    public static function cleanupSystem(): void
    {
        self::stopService();
        if (is_link(self::LINK)) {
            unlink(self::LINK);
        }
        self::run('rm -rf ' . self::DPKG_DIR);
        if (self::run('id ' . self::USER) === 0) {
            self::run(Util::which('deluser') . ' ' . self::USER);
            self::run(Util::which('delgroup') . ' ' . self::USER);
        }
    }

    /**
     * @return array{running: bool, daemons: array<string, string>, connected: bool, lastKeepalive: string,
     *               agentId: string, version: string, manager: string, packages: int}
     */
    public static function getStatus(): array
    {
        $home = self::agentHome();
        $daemons = [];
        foreach (self::DAEMONS as $daemon) {
            $daemons[$daemon] = self::pidOf($daemon);
        }
        $state = @file_get_contents("$home/var/run/wazuh-agentd.state") ?: '';
        preg_match("/^status='([^']*)'/m", $state, $status);
        preg_match("/^last_keepalive='([^']*)'/m", $state, $keepalive);
        $agentId = trim(@file_get_contents(self::agentIdPath()) ?: '');
        $settings = self::getSettings();
        $dpkg = @file_get_contents(self::DPKG_DIR . '/status') ?: '';

        return [
            'running' => !in_array('', $daemons, true),
            'daemons' => $daemons,
            'connected' => ($status[1] ?? '') === 'connected',
            'lastKeepalive' => $keepalive[1] ?? '',
            'agentId' => $agentId,
            'version' => self::AGENT_VERSION,
            'manager' => $settings === null ? '' : "{$settings->managerAddress}:{$settings->managerPort}",
            'packages' => substr_count($dpkg, "\nPackage: ") + (str_starts_with($dpkg, 'Package: ') ? 1 : 0),
        ];
    }

    /**
     * The root file system is tmpfs: user, group and /var/ossec are recreated on every start.
     */
    private static function prepareSystem(): void
    {
        if (self::run('id ' . self::USER) !== 0) {
            self::run(Util::which('addgroup') . ' -S ' . self::USER);
            self::run(Util::which('adduser') . ' -S -D -H -h ' . self::LINK . ' -s /bin/false -G ' . self::USER . ' ' . self::USER);
        }
        self::run('ln -sfn ' . escapeshellarg(self::agentHome()) . ' ' . self::LINK);
    }

    /**
     * Writes /var/lib/dpkg/status from the image manifest.
     *
     * Manifest columns: source, package, version, NVD product, NVD vendor ("-" when unmapped).
     * The vendor goes to Maintainer: Wazuh matches it exactly against the CVE vendor.
     */
    private static function writeInventory(): void
    {
        $lines = @file(self::MANIFEST, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            SystemMessages::sysLogMsg(self::MODULE_ID, self::MANIFEST . ' not found, inventory is empty');
            $lines = [];
        }
        $arch = php_uname('m') === 'aarch64' ? 'arm64' : 'amd64';
        $entries = [];
        foreach ($lines as $line) {
            $cols = explode("\t", $line);
            if (count($cols) < 5) {
                continue;
            }
            [$source, $name, $version, $product, $vendor] = $cols;
            // ponytail: Composer rows skipped (names contain "/", CVEs come from Dependabot); module vendors in stage 2.
            if ($source === 'composer') {
                continue;
            }
            $package = $product !== '-' ? $product : $name;
            $maintainer = $vendor !== '-' ? $vendor : 'mikopbx';
            $entries["$package|$version|$maintainer"] = "Package: $package\n"
                . "Status: install ok installed\n"
                . "Priority: optional\n"
                . "Section: mikopbx\n"
                . "Maintainer: $maintainer\n"
                . "Architecture: $arch\n"
                . "Version: $version\n"
                . "Description: MikoPBX $source package $name\n";
        }
        Util::mwMkdir(self::DPKG_DIR . '/info');
        $tmp = self::DPKG_DIR . '/status.tmp';
        file_put_contents($tmp, implode("\n", $entries));
        rename($tmp, self::DPKG_DIR . '/status');
    }

    private static function writeConfig(ModuleWazuhAgent $settings): void
    {
        $x = static fn (?string $v): string => htmlspecialchars(trim((string)$v), ENT_XML1 | ENT_QUOTES);
        $port = (int)$settings->managerPort > 0 ? (int)$settings->managerPort : 1514;
        $enrollment = '';
        if (trim((string)$settings->agentName) !== '') {
            $enrollment .= "\n      <agent_name>{$x($settings->agentName)}</agent_name>";
        }
        if (trim((string)$settings->agentGroup) !== '') {
            $enrollment .= "\n      <groups>{$x($settings->agentGroup)}</groups>";
        }
        $passFile = self::agentHome() . '/etc/authd.pass';
        if ((string)$settings->registrationPassword !== '') {
            file_put_contents($passFile, $settings->registrationPassword);
            $enrollment .= "\n      <authorization_pass_path>etc/authd.pass</authorization_pass_path>";
        } elseif (is_file($passFile)) {
            unlink($passFile);
        }

        $config = <<<XML
<ossec_config>
  <client>
    <server>
      <address>{$x($settings->managerAddress)}</address>
      <port>$port</port>
      <protocol>tcp</protocol>
    </server>
    <notify_time>20</notify_time>
    <time-reconnect>60</time-reconnect>
    <auto_restart>no</auto_restart>
    <crypto_method>aes</crypto_method>
    <enrollment>
      <enabled>yes</enabled>$enrollment
    </enrollment>
  </client>

  <logging>
    <log_format>plain</log_format>
  </logging>

  <rootcheck>
    <disabled>yes</disabled>
  </rootcheck>

  <syscheck>
    <disabled>yes</disabled>
  </syscheck>

  <sca>
    <enabled>no</enabled>
  </sca>

  <active-response>
    <disabled>yes</disabled>
  </active-response>

  <wodle name="syscollector">
    <disabled>no</disabled>
    <interval>1h</interval>
    <scan_on_start>yes</scan_on_start>
    <hardware>yes</hardware>
    <os>yes</os>
    <network>no</network>
    <packages>yes</packages>
    <ports all="no">no</ports>
    <processes>no</processes>
  </wodle>
</ossec_config>

XML;
        file_put_contents(self::agentHome() . '/etc/ossec.conf', $config);
    }

    private static function restoreKeys(): void
    {
        $keys = self::agentHome() . '/etc/client.keys';
        if (is_file(self::savedKeysPath()) && filesize(self::savedKeysPath()) > 0 && !(is_file($keys) && filesize($keys) > 0)) {
            copy(self::savedKeysPath(), $keys);
        }
        if (!is_file($keys)) {
            touch($keys);
        }
    }

    private static function saveKeys(): void
    {
        $keys = @file_get_contents(self::agentHome() . '/etc/client.keys');
        if ($keys === false || $keys === '') {
            return;
        }
        if ($keys !== @file_get_contents(self::savedKeysPath())) {
            file_put_contents(self::savedKeysPath(), $keys);
        }
        // Always: a module update restores db/ from backup and resets it to www:www 644.
        chown(self::savedKeysPath(), 'root');
        chmod(self::savedKeysPath(), 0600);
        // ID and name only, for the status page (www cannot read the key).
        $fields = explode(' ', $keys);
        file_put_contents(self::agentIdPath(), $fields[0] . ' ' . ($fields[1] ?? ''));
    }

    /**
     * #6: name, groups and manager take effect only at enrollment, so a change drops the key
     * and the agent enrolls again. A missing fingerprint (update from 0.1.2) keeps the key.
     */
    private static function forgetKeysIfEnrollmentChanged(ModuleWazuhAgent $settings): void
    {
        $path = self::moduleDir() . '/db/enrollment';
        $current = implode('|', array_map(
            static fn ($v): string => trim((string)$v),
            [$settings->managerAddress, $settings->agentName, $settings->agentGroup]
        ));
        $previous = @file_get_contents($path);
        if ($previous === $current) {
            return;
        }
        if ($previous !== false) {
            SystemMessages::sysLogMsg(self::MODULE_ID, 'Manager, name or groups changed, the agent enrolls again');
            foreach ([self::agentHome() . '/etc/client.keys', self::savedKeysPath(), self::agentIdPath()] as $file) {
                if (is_file($file) && !unlink($file)) {
                    // Keep the old fingerprint: the change is applied again on the next start.
                    return;
                }
            }
        }
        // Atomic: a truncated fingerprint (full disk) would drop a valid key on the next start.
        if (file_put_contents("$path.tmp", $current) === strlen($current)) {
            rename("$path.tmp", $path);
        }
    }

    private static function fixRights(): void
    {
        $home = escapeshellarg(self::agentHome());
        $u = self::USER;
        self::run("chown -R root:$u $home && chmod -R g+rX,o-rwx $home");
        foreach (['queue', 'var', 'logs', 'tmp', 'backup', 'etc/shared'] as $dir) {
            self::run("chown -R $u:$u $home/$dir");
        }
        self::run("chmod 770 $home/etc && chown $u:$u $home/etc/client.keys && chmod 640 $home/etc/client.keys");
        // The status page runs as www: traverse-only access to reach the world-readable wazuh-agentd.state.
        self::run("chmod o+x $home $home/var $home/var/run");
    }

    /** @return string[] */
    private static function runningPids(): array
    {
        return array_values(array_filter(array_map([self::class, 'pidOf'], self::DAEMONS)));
    }

    private static function pidOf(string $daemon): string
    {
        $out = [];
        Processes::mwExec(Util::which('pidof') . ' ' . escapeshellarg($daemon), $out);
        return trim(implode(' ', $out));
    }

    private static function run(string $command): int
    {
        $out = [];
        Processes::mwExec($command, $out, $code);
        return (int)$code;
    }
}
