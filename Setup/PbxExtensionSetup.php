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

namespace Modules\ModuleWazuhAgent\Setup;

use MikoPBX\Modules\Setup\PbxExtensionSetupBase;
use Modules\ModuleWazuhAgent\Lib\WazuhAgentMain;
use Throwable;

class PbxExtensionSetup extends PbxExtensionSetupBase
{
    /**
     * The agent is downloaded after the base class has reset module files to www/644,
     * otherwise its binaries would lose the executable bit.
     */
    public function fixFilesRights(): bool
    {
        parent::fixFilesRights();
        // Core restores db/ from backup as www:644; the agent key must stay root-only.
        $keys = WazuhAgentMain::moduleDir() . "/db/client.keys";
        if (is_file($keys)) {
            chown($keys, "root");
            chmod($keys, 0600);
        }
        try {
            WazuhAgentMain::installAgent();
        } catch (Throwable $e) {
            $this->messages[] = $e->getMessage();
            return false;
        }
        return true;
    }

    public function unInstallFiles(bool $keepSettings = false): bool
    {
        WazuhAgentMain::cleanupSystem();
        return parent::unInstallFiles($keepSettings);
    }
}
