function loadApi(apiId) {
	$("#api-btn-"+apiId).hide();
	$("#api-btn-hide-"+apiId).show();
	$("#call-api-"+apiId).slideDown('slow');
}

function hideApi(apiId) {
	$("#api-btn-"+apiId).show();
	$("#api-btn-hide-"+apiId).hide();
	$("#call-api-"+apiId).slideUp('slow');
}

function tokenUser() {
	let user = $("#user-selector").find(":selected").val();
	callApi("AdminUser", "GetUserToken", { id: user })
		.then(rs => {
			console.info(rs);
			if(!rs.success) {
				showToast(rs.data?.error, "Error on authorization", true);
				return;
			}
			let data = rs.data;
			$("#token").val(data.token);
		});
}

function executeApi(apiId) {
	let api = $("#api-"+apiId).html();
	let method = $("#api-method-"+apiId).html();
	let payload = $("#api-payload-"+apiId).val();
	let token = $("#token").val();
	let url = $("#api-url").val();
	debugAPI("new call ==["+now()+"]========> ");
	debugAPI("\tcalling ("+method+")["+api+"]");
	debugAPI("\t\t " + url + api);
	if(payload) {
		debugAPI("\tpayload: ["+payload+"]");
		try {
			payloadJson = JSON.parse(payload);
		} catch(err) {
			console.error("payload error", err);
			showToast("Check debug for more info", "Bad Payload", true);
			debugAPI("\tpayload error: " + err);
			debugAPI("\n");
			return;
		}
	} else {
		payloadJson = null;
		debugAPI("\tno payload");
	}
	ajax(method, api, payloadJson, token, true)
		.then(rs => {
			let response = jsonAPIFormat(rs);
			debugAPI("\nrs:");
			debugAPI(response);
			debugAPI("\n");
			return response;
		})
		.catch(err => {
			debugAPI("\nERROR: " + err.error);
			if(err.data) {
				rs = jsonAPIFormat(err.data);
				debugAPI(rs);
				return rs;
			} else {
				return err.error;
			}
		})
		.then(rs => showOn("#api-rs-" + apiId, rs));
}

function updateApiUrl(apiId, param, el) {
	let apiOriginal = $("#api-original-"+apiId).val();
	let apiName = $("#api-"+apiId);
	let val = $(el).val();
	let url = apiOriginal.replace(':'+param, val);
	apiName.html(url);
}

function debugAPI(data) {
	$("#api-debug").append(data+"\n");
}
function clearDebugAPI() {
	$("#api-debug").html("");
}

function jsonAPIFormat(data) {
	var jsonString = JSON.stringify(data, null, 2);
	jsonString = jsonString.replace(/\n/g, '<br>').replace(/\t/g, '&nbsp;&nbsp;&nbsp;&nbsp;');
	return jsonString;
}
