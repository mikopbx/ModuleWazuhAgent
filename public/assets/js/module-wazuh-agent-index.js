"use strict";

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]; if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/* global globalRootUrl, globalTranslate, Form */
var ModuleWazuhAgent = {
  $formObj: null,
  statusTimer: null,
  initialize: function initialize() {
    ModuleWazuhAgent.$formObj = $('#module-wazuh-agent-form');
    Form.$formObj = ModuleWazuhAgent.$formObj;
    Form.url = "".concat(globalRootUrl, "module-wazuh-agent/save");
    Form.validateRules = {
      managerAddress: {
        identifier: 'managerAddress',
        rules: [{
          type: 'empty',
          prompt: globalTranslate.module_wazuh_agent_ValidateManagerAddress
        }]
      },
      managerPort: {
        identifier: 'managerPort',
        optional: true,
        rules: [{
          type: 'integer[1..65535]',
          prompt: globalTranslate.module_wazuh_agent_ValidateManagerPort
        }]
      }
    };

    Form.cbBeforeSendForm = function (settings) {
      return _objectSpread(_objectSpread({}, settings), {}, {
        data: ModuleWazuhAgent.$formObj.form('get values')
      });
    };

    Form.cbAfterSendForm = function () {
      ModuleWazuhAgent.$formObj.find('input[name="registrationPassword"]').val('');
    };

    Form.initialize();
    ModuleWazuhAgent.updateStatus();
  },
  updateStatus: function updateStatus() {
    window.clearTimeout(ModuleWazuhAgent.statusTimer);
    $.api({
      url: "".concat(globalRootUrl, "module-wazuh-agent/status"),
      on: 'now',
      onSuccess: function onSuccess(response) {
        ModuleWazuhAgent.showStatus(response.data);
      },
      onComplete: function onComplete() {
        ModuleWazuhAgent.statusTimer = window.setTimeout(ModuleWazuhAgent.updateStatus, 10000);
      }
    });
  },
  showStatus: function showStatus(data) {
    var ok = data.running && data.connected;
    $('#wazuh-status').toggleClass('positive', ok).toggleClass('negative', !ok);
    $('#wazuh-status-icon').removeClass('spinner loading check exclamation circle').addClass(ok ? 'check circle' : 'exclamation circle');
    var daemons = Object.entries(data.daemons).map(function (_ref) {
      var _ref2 = _slicedToArray(_ref, 2),
          name = _ref2[0],
          pid = _ref2[1];

      return "".concat(name, ": ").concat(pid || globalTranslate.module_wazuh_agent_Stopped);
    }).join(', ');
    $('#wazuh-status-daemons').text(daemons);
    var connection = data.connected ? "".concat(globalTranslate.module_wazuh_agent_Connected, " ").concat(data.manager, " (").concat(data.lastKeepalive, ")") : globalTranslate.module_wazuh_agent_NotConnected;
    $('#wazuh-status-connection').text(connection);
    $('#wazuh-status-agent-id').text(data.agentId || '—');
    $('#wazuh-status-packages').text(data.packages);
    $('#wazuh-status-version').text(data.version);
  }
};
$(document).ready(function () {
  ModuleWazuhAgent.initialize();
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9tb2R1bGUtd2F6dWgtYWdlbnQtaW5kZXguanMiXSwibmFtZXMiOlsiTW9kdWxlV2F6dWhBZ2VudCIsIiRmb3JtT2JqIiwic3RhdHVzVGltZXIiLCJpbml0aWFsaXplIiwiJCIsIkZvcm0iLCJ1cmwiLCJnbG9iYWxSb290VXJsIiwidmFsaWRhdGVSdWxlcyIsIm1hbmFnZXJBZGRyZXNzIiwiaWRlbnRpZmllciIsInJ1bGVzIiwidHlwZSIsInByb21wdCIsImdsb2JhbFRyYW5zbGF0ZSIsIm1vZHVsZV93YXp1aF9hZ2VudF9WYWxpZGF0ZU1hbmFnZXJBZGRyZXNzIiwibWFuYWdlclBvcnQiLCJvcHRpb25hbCIsIm1vZHVsZV93YXp1aF9hZ2VudF9WYWxpZGF0ZU1hbmFnZXJQb3J0IiwiY2JCZWZvcmVTZW5kRm9ybSIsInNldHRpbmdzIiwiZGF0YSIsImZvcm0iLCJjYkFmdGVyU2VuZEZvcm0iLCJmaW5kIiwidmFsIiwidXBkYXRlU3RhdHVzIiwid2luZG93IiwiY2xlYXJUaW1lb3V0IiwiYXBpIiwib24iLCJvblN1Y2Nlc3MiLCJyZXNwb25zZSIsInNob3dTdGF0dXMiLCJvbkNvbXBsZXRlIiwic2V0VGltZW91dCIsIm9rIiwicnVubmluZyIsImNvbm5lY3RlZCIsInRvZ2dsZUNsYXNzIiwicmVtb3ZlQ2xhc3MiLCJhZGRDbGFzcyIsImRhZW1vbnMiLCJPYmplY3QiLCJlbnRyaWVzIiwibWFwIiwibmFtZSIsInBpZCIsIm1vZHVsZV93YXp1aF9hZ2VudF9TdG9wcGVkIiwiam9pbiIsInRleHQiLCJjb25uZWN0aW9uIiwibW9kdWxlX3dhenVoX2FnZW50X0Nvbm5lY3RlZCIsIm1hbmFnZXIiLCJsYXN0S2VlcGFsaXZlIiwibW9kdWxlX3dhenVoX2FnZW50X05vdENvbm5lY3RlZCIsImFnZW50SWQiLCJwYWNrYWdlcyIsInZlcnNpb24iLCJkb2N1bWVudCIsInJlYWR5Il0sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7Ozs7Ozs7Ozs7OztBQUFBO0FBRUEsSUFB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QWV2QixJQUFJLENBQUNxQixPQUFwQixFQUNkRyxHQURjLENBQ1Y7QUFBQTtBQUFBLFVBQUVDLElBQUY7QUFBQSxVQUFRQyxHQUFSOztBQUFBLHVCQUFvQkQsSUFBcEIsZUFBNkJDLEdBQUcsSUFBSWpDLGVBQWUsQ0FBQ2tDLDBCQUFwRDtBQUFBLEtBRFUsRUFFZEMsSUFGYyxDQUVULElBRlMsQ0FBaEI7QUFHQTdDLElBQUFBLENBQUMsQ0FBQyx1QkFBRCxDQUFELENBQTJCOEMsSUFBM0IsQ0FBZ0NSLE9BQWhDO0FBQ0EsUUFBTVMsVUFBVSxHQUFHOUIsSUFBSSxDQUFDaUIsU0FBTCxhQUNieEIsZUFBZSxDQUFDc0MsNEJBREgsY0FDbUMvQixJQUFJLENBQUNnQyxPQUR4QyxlQUNvRGhDLElBQUksQ0FBQ2lDLGFBRHpELFNBRWhCeEMsZUFBZSxDQUFDeUMsK0JBRm5CO0FBR0FuRCxJQUFBQSxDQUFDLENBQUMsMEJBQUQsQ0FBRCxDQUE4QjhDLElBQTlCLENBQW1DQyxVQUFuQztBQUNBL0MsSUFBQUEsQ0FBQyxDQUFDLHdCQUFELENBQUQsQ0FBNEI4QyxJQUE1QixDQUFpQzdCLElBQUksQ0FBQ21DLE9BQUwsSUFBZ0IsR0FBakQ7QUFDQXBELElBQUFBLENBQUMsQ0FBQyx3QkFBRCxDQUFELENBQTRCOEMsSUFBNUIsQ0FBaUM3QixJQUFJLENBQUNvQyxRQUF0QztBQUNBckQsSUFBQUEsQ0FBQyxDQUFDLHVCQUFELENBQUQsQ0FBMkI4QyxJQUEzQixDQUFnQzdCLElBQUksQ0FBQ3FDLE9BQXJDO0FBQ0E7QUExRHVCLENBQXpCO0FBNkRBdEQsQ0FBQyxDQUFDdUQsUUFBRCxDQUFELENBQVlDLEtBQVosQ0FBa0IsWUFBTTtBQUN2QjVELEVBQUFBLGdCQUFnQixDQUFDRyxVQUFqQjtBQUNBLENBRkQiLCJzb3VyY2VzQ29udGVudCI6WyIvKiBnbG9iYWwgZ2xvYmFsUm9vdFVybCwgZ2xvYmFsVHJhbnNsYXRlLCBGb3JtICovXG5cbmNvbnN0IE1vZHVsZVdhenVoQWdlbnQgPSB7XG5cdCRmb3JtT2JqOiBudWxsLFxuXHRzdGF0dXNUaW1lcjogbnVsbCxcblxuXHRpbml0aWFsaXplKCkge1xuXHRcdE1vZHVsZVdhenVoQWdlbnQuJGZvcm1PYmogPSAkKCcjbW9kdWxlLXdhenVoLWFnZW50LWZvcm0nKTtcblx0XHRGb3JtLiRmb3JtT2JqID0gTW9kdWxlV2F6dWhBZ2VudC4kZm9ybU9iajtcblx0XHRGb3JtLnVybCA9IGAke2dsb2JhbFJvb3RVcmx9bW9kdWxlLXdhenVoLWFnZW50L3NhdmVgO1xuXHRcdEZvcm0udmFsaWRhdGVSdWxlcyA9IHtcblx0XHRcdG1hbmFnZXJBZGRyZXNzOiB7XG5cdFx0XHRcdGlkZW50aWZpZXI6ICdtYW5hZ2VyQWRkcmVzcycsXG5cdFx0XHRcdHJ1bGVzOiBbeyB0eXBlOiAnZW1wdHknLCBwcm9tcHQ6IGdsb2JhbFRyYW5zbGF0ZS5tb2R1bGVfd2F6dWhfYWdlbnRfVmFsaWRhdGVNYW5hZ2VyQWRkcmVzcyB9XSxcblx0XHRcdH0sXG5cdFx0XHRtYW5hZ2VyUG9ydDoge1xuXHRcdFx0XHRpZGVudGlmaWVyOiAnbWFuYWdlclBvcnQnLFxuXHRcdFx0XHRvcHRpb25hbDogdHJ1ZSxcblx0XHRcdFx0cnVsZXM6IFt7IHR5cGU6ICdpbnRlZ2VyWzEuLjY1NTM1XScsIHByb21wdDogZ2xvYmFsVHJhbnNsYXRlLm1vZHVsZV93YXp1aF9hZ2VudF9WYWxpZGF0ZU1hbmFnZXJQb3J0IH1dLFxuXHRcdFx0fSxcblx0XHR9O1xuXHRcdEZvcm0uY2JCZWZvcmVTZW5kRm9ybSA9IChzZXR0aW5ncykgPT4gKHsgLi4uc2V0dGluZ3MsIGRhdGE6IE1vZHVsZVdhenVoQWdlbnQuJGZvcm1PYmouZm9ybSgnZ2V0IHZhbHVlcycpIH0pO1xuXHRcdEZvcm0uY2JBZnRlclNlbmRGb3JtID0gKCkgPT4ge1xuXHRcdFx0TW9kdWxlV2F6dWhBZ2VudC4kZm9ybU9iai5maW5kKCdpbnB1dFtuYW1lPVwicmVnaXN0cmF0aW9uUGFzc3dvcmRcIl0nKS52YWwoJycpO1xuXHRcdH07XG5cdFx0Rm9ybS5pbml0aWFsaXplKCk7XG5cdFx0TW9kdWxlV2F6dWhBZ2VudC51cGRhdGVTdGF0dXMoKTtcblx0fSxcblxuXHR1cGRhdGVTdGF0dXMoKSB7XG5cdFx0d2luZG93LmNsZWFyVGltZW91dChNb2R1bGVXYXp1aEFnZW50LnN0YXR1c1RpbWVyKTtcblx0XHQkLmFwaSh7XG5cdFx0XHR1cmw6IGAke2dsb2JhbFJvb3RVcmx9bW9kdWxlLXdhenVoLWFnZW50L3N0YXR1c2AsXG5cdFx0XHRvbjogJ25vdycsXG5cdFx0XHRvblN1Y2Nlc3MocmVzcG9uc2UpIHtcblx0XHRcdFx0TW9kdWxlV2F6dWhBZ2VudC5zaG93U3RhdHVzKHJlc3BvbnNlLmRhdGEpO1xuXHRcdFx0fSxcblx0XHRcdG9uQ29tcGxldGUoKSB7XG5cdFx0XHRcdE1vZHVsZVdhenVoQWdlbnQuc3RhdHVzVGltZXIgPSB3aW5kb3cuc2V0VGltZW91dChNb2R1bGVXYXp1aEFnZW50LnVwZGF0ZVN0YXR1cywgMTAwMDApO1xuXHRcdFx0fSxcblx0XHR9KTtcblx0fSxcblxuXHRzaG93U3RhdHVzKGRhdGEpIHtcblx0XHRjb25zdCBvayA9IGRhdGEucnVubmluZyAmJiBkYXRhLmNvbm5lY3RlZDtcblx0XHQkKCcjd2F6dWgtc3RhdHVzJykudG9nZ2xlQ2xhc3MoJ3Bvc2l0aXZlJywgb2spLnRvZ2dsZUNsYXNzKCduZWdhdGl2ZScsICFvayk7XG5cdFx0JCgnI3dhenVoLXN0YXR1cy1pY29uJylcblx0XHRcdC5yZW1vdmVDbGFzcygnc3Bpbm5lciBsb2FkaW5nIGNoZWNrIGV4Y2xhbWF0aW9uIGNpcmNsZScpXG5cdFx0XHQuYWRkQ2xhc3Mob2sgPyAnY2hlY2sgY2lyY2xlJyA6ICdleGNsYW1hdGlvbiBjaXJjbGUnKTtcblx0XHRjb25zdCBkYWVtb25zID0gT2JqZWN0LmVudHJpZXMoZGF0YS5kYWVtb25zKVxuXHRcdFx0Lm1hcCgoW25hbWUsIHBpZF0pID0+IGAke25hbWV9OiAke3BpZCB8fCBnbG9iYWxUcmFuc2xhdGUubW9kdWxlX3dhenVoX2FnZW50X1N0b3BwZWR9YClcblx0XHRcdC5qb2luKCcsICcpO1xuXHRcdCQoJyN3YXp1aC1zdGF0dXMtZGFlbW9ucycpLnRleHQoZGFlbW9ucyk7XG5cdFx0Y29uc3QgY29ubmVjdGlvbiA9IGRhdGEuY29ubmVjdGVkXG5cdFx0XHQ/IGAke2dsb2JhbFRyYW5zbGF0ZS5tb2R1bGVfd2F6dWhfYWdlbnRfQ29ubmVjdGVkfSAke2RhdGEubWFuYWdlcn0gKCR7ZGF0YS5sYXN0S2VlcGFsaXZlfSlgXG5cdFx0XHQ6IGdsb2JhbFRyYW5zbGF0ZS5tb2R1bGVfd2F6dWhfYWdlbnRfTm90Q29ubmVjdGVkO1xuXHRcdCQoJyN3YXp1aC1zdGF0dXMtY29ubmVjdGlvbicpLnRleHQoY29ubmVjdGlvbik7XG5cdFx0JCgnI3dhenVoLXN0YXR1cy1hZ2VudC1pZCcpLnRleHQoZGF0YS5hZ2VudElkIHx8ICfigJQnKTtcblx0XHQkKCcjd2F6dWgtc3RhdHVzLXBhY2thZ2VzJykudGV4dChkYXRhLnBhY2thZ2VzKTtcblx0XHQkKCcjd2F6dWgtc3RhdHVzLXZlcnNpb24nKS50ZXh0KGRhdGEudmVyc2lvbik7XG5cdH0sXG59O1xuXG4kKGRvY3VtZW50KS5yZWFkeSgoKSA9PiB7XG5cdE1vZHVsZVdhenVoQWdlbnQuaW5pdGlhbGl6ZSgpO1xufSk7XG4iXX0=