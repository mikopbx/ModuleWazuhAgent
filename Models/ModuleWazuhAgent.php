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

namespace Modules\ModuleWazuhAgent\Models;

use MikoPBX\Modules\Models\ModulesModelsBase;

class ModuleWazuhAgent extends ModulesModelsBase
{
    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * Wazuh manager address (host name or IP)
     *
     * @Column(type="string", nullable=true)
     */
    public ?string $managerAddress = '';

    /**
     * Manager port for agent events
     *
     * @Column(type="string", nullable=true)
     */
    public ?string $managerPort = '1514';

    /**
     * Enrollment password; never rendered back to the browser
     *
     * @Column(type="string", nullable=true)
     */
    public ?string $registrationPassword = '';

    /**
     * Agent name on the manager; empty means the station host name
     *
     * @Column(type="string", nullable=true)
     */
    public ?string $agentName = '';

    /**
     * Comma-separated agent groups on the manager
     *
     * @Column(type="string", nullable=true)
     */
    public ?string $agentGroup = '';

    public function initialize(): void
    {
        $this->setSource('m_ModuleWazuhAgent');
        parent::initialize();
    }
}
