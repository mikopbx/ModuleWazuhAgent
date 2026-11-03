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

namespace Modules\ModuleWazuhAgent\App\Forms;

use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Form;

class ModuleWazuhAgentForm extends Form
{
    public function initialize($entity = null, $options = null): void
    {
        $this->add(new Hidden('id'));
        $this->add(new Text('managerAddress', ['placeholder' => 'wazuh.example.com']));
        $this->add(new Text('managerPort', ['placeholder' => '1514']));
        $this->add(new Text('agentName'));
        $this->add(new Text('agentGroup', ['placeholder' => 'default']));
    }
}
