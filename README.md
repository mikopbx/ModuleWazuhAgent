# ModuleWazuhAgent

MikoPBX module that runs the official Wazuh agent on the station and sends the station package
inventory to your Wazuh manager, so that the manager can detect vulnerable versions.

Status: test version 0.1 (x86_64 only). See [issue #1](https://github.com/mikopbx/ModuleWazuhAgent/issues/1).

## How it works

- At installation the station downloads `wazuh-agent 4.14.9-1` (amd64) from packages.wazuh.com
  and checks its SHA-256. The station must reach packages.wazuh.com once.
- MikoPBX has no package database. At every start the module writes `/var/lib/dpkg/status`
  from the image manifest `/offload/package-manifest.tsv` (MikoPBX 2026.6.80 or later).
  Each package carries the NVD product and vendor, so the manager matches CVEs.
- The agent runs only `wazuh-agentd` and `wazuh-modulesd` with syscollector. SCA, file
  integrity, log collection, active response and remote commands are off.
- The agent key is kept in the module database folder and survives reboots and module updates.

## Settings

| Field | Meaning |
|---|---|
| Manager address | Host name or IP of the Wazuh manager |
| Manager port | Port for agent events, 1514 by default (enrollment uses 1515) |
| Enrollment password | Only if the manager requires `authd.pass`. Never shown after saving |
| Agent name | Name on the manager; the station host name if empty |
| Agent groups | Comma-separated groups for enrollment |

Network: outgoing TCP 1514 and 1515 to the manager. No incoming port is opened.

## Known limits

- False positives are expected: version matching does not see MIKO patches, and kernel CVEs
  are not filtered by the build configuration.
- Composer dependencies are not in the inventory.

## Support

help@miko.ru
