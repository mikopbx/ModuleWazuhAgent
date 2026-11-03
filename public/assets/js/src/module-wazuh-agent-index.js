/* global globalRootUrl, globalTranslate, Form */

const ModuleWazuhAgent = {
	$formObj: null,
	statusTimer: null,

	initialize() {
		ModuleWazuhAgent.$formObj = $('#module-wazuh-agent-form');
		Form.$formObj = ModuleWazuhAgent.$formObj;
		Form.url = `${globalRootUrl}module-wazuh-agent/save`;
		Form.validateRules = {
			managerAddress: {
				identifier: 'managerAddress',
				rules: [{ type: 'empty', prompt: globalTranslate.module_wazuh_agent_ValidateManagerAddress }],
			},
			managerPort: {
				identifier: 'managerPort',
				optional: true,
				rules: [{ type: 'integer[1..65535]', prompt: globalTranslate.module_wazuh_agent_ValidateManagerPort }],
			},
		};
		Form.cbBeforeSendForm = (settings) => ({ ...settings, data: ModuleWazuhAgent.$formObj.form('get values') });
		Form.cbAfterSendForm = () => {
			ModuleWazuhAgent.$formObj.find('input[name="registrationPassword"]').val('');
		};
		Form.initialize();
		ModuleWazuhAgent.updateStatus();
	},

	updateStatus() {
		window.clearTimeout(ModuleWazuhAgent.statusTimer);
		$.api({
			url: `${globalRootUrl}module-wazuh-agent/status`,
			on: 'now',
			onSuccess(response) {
				ModuleWazuhAgent.showStatus(response.data);
			},
			onComplete() {
				ModuleWazuhAgent.statusTimer = window.setTimeout(ModuleWazuhAgent.updateStatus, 10000);
			},
		});
	},

	showStatus(data) {
		const ok = data.running && data.connected;
		$('#wazuh-status').toggleClass('positive', ok).toggleClass('negative', !ok);
		$('#wazuh-status-icon')
			.removeClass('spinner loading check exclamation circle')
			.addClass(ok ? 'check circle' : 'exclamation circle');
		const daemons = Object.entries(data.daemons)
			.map(([name, pid]) => `${name}: ${pid || globalTranslate.module_wazuh_agent_Stopped}`)
			.join(', ');
		$('#wazuh-status-daemons').text(daemons);
		const connection = data.connected
			? `${globalTranslate.module_wazuh_agent_Connected} ${data.manager} (${data.lastKeepalive})`
			: globalTranslate.module_wazuh_agent_NotConnected;
		$('#wazuh-status-connection').text(connection);
		$('#wazuh-status-agent-id').text(data.agentId || '—');
		$('#wazuh-status-packages').text(data.packages);
		$('#wazuh-status-version').text(data.version);
	},
};

$(document).ready(() => {
	ModuleWazuhAgent.initialize();
});
