<form method="post" action="module-wazuh-agent/save" role="form" class="ui form large" id="module-wazuh-agent-form">
    {{ form.render('id') }}

    <div id="wazuh-status" class="ui icon message">
        <i class="spinner loading icon" id="wazuh-status-icon"></i>
        <div class="content">
            <div class="header">{{ t._('module_wazuh_agent_Status') }}</div>
            <table class="ui very basic compact table">
                <tbody>
                <tr><td class="four wide">{{ t._('module_wazuh_agent_Daemons') }}</td><td id="wazuh-status-daemons">—</td></tr>
                <tr><td>{{ t._('module_wazuh_agent_Connection') }}</td><td id="wazuh-status-connection">—</td></tr>
                <tr><td>{{ t._('module_wazuh_agent_AgentId') }}</td><td id="wazuh-status-agent-id">—</td></tr>
                <tr><td>{{ t._('module_wazuh_agent_Packages') }}</td><td id="wazuh-status-packages">—</td></tr>
                <tr><td>{{ t._('module_wazuh_agent_Version') }}</td><td id="wazuh-status-version">—</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="two fields">
        <div class="field">
            <label>{{ t._('module_wazuh_agent_ManagerAddress') }}</label>
            {{ form.render('managerAddress') }}
        </div>
        <div class="field">
            <label>{{ t._('module_wazuh_agent_ManagerPort') }}</label>
            {{ form.render('managerPort') }}
        </div>
    </div>
    <div class="field">
        <label>{{ t._('module_wazuh_agent_RegistrationPassword') }}</label>
        <input type="password" name="registrationPassword" autocomplete="new-password"
               placeholder="{{ passwordIsSet ? t._('module_wazuh_agent_PasswordIsSet') : '' }}">
    </div>
    <div class="two fields">
        <div class="field">
            <label>{{ t._('module_wazuh_agent_AgentName') }}</label>
            {{ form.render('agentName') }}
        </div>
        <div class="field">
            <label>{{ t._('module_wazuh_agent_AgentGroup') }}</label>
            {{ form.render('agentGroup') }}
        </div>
    </div>

    {{ partial("partials/submitbutton",['indexurl':'pbx-extension-modules/index/']) }}
</form>
