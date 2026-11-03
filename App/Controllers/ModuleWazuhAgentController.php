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

namespace Modules\ModuleWazuhAgent\App\Controllers;

use MikoPBX\AdminCabinet\Controllers\BaseController;
use MikoPBX\AdminCabinet\Providers\AssetProvider;
use Modules\ModuleWazuhAgent\App\Forms\ModuleWazuhAgentForm;
use Modules\ModuleWazuhAgent\Lib\WazuhAgentMain;
use Modules\ModuleWazuhAgent\Models\ModuleWazuhAgent;

class ModuleWazuhAgentController extends BaseController
{
    private const array FIELDS = ['managerAddress', 'managerPort', 'registrationPassword', 'agentName', 'agentGroup'];

    public function initialize(): void
    {
        $id = WazuhAgentMain::MODULE_ID;
        $this->view->logoImagePath = "{$this->url->get()}assets/img/cache/$id/logo.svg";
        $this->view->submitMode = null;
        parent::initialize();
    }

    public function indexAction(): void
    {
        $id = WazuhAgentMain::MODULE_ID;
        $this->assets->collection(AssetProvider::FOOTER_JS)
            ->addJs('js/pbx/main/form.js', true)
            ->addJs("js/cache/$id/module-wazuh-agent-index.js", true);

        $settings = WazuhAgentMain::getSettings() ?? new ModuleWazuhAgent();
        $this->view->passwordIsSet = (string)$settings->registrationPassword !== '';
        $this->view->form = new ModuleWazuhAgentForm($settings);
        $this->view->pick(WazuhAgentMain::moduleDir() . '/App/Views/index');
    }

    public function saveAction(): void
    {
        if (!$this->request->isPost()) {
            return;
        }
        $data = $this->request->getPost();
        $record = WazuhAgentMain::getSettings() ?? new ModuleWazuhAgent();
        foreach (self::FIELDS as $field) {
            $value = trim((string)($data[$field] ?? ''));
            // The password is never sent to the browser: an empty field keeps the stored one.
            if ($field === 'registrationPassword' && $value === '') {
                continue;
            }
            $record->$field = $value;
        }
        $this->saveEntity($record);
    }

    public function statusAction(): void
    {
        $this->view->data = WazuhAgentMain::getStatus();
    }
}
