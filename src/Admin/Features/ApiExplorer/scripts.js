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

function toggleApi(apiId) {
	$("#api-btn-"+apiId).toggle();
	$("#api-btn-hide-"+apiId).toggle();
	$("#call-api-"+apiId).slideToggle('slow');
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
	const api = $("#api-endpoint-"+apiId).html();
	const method = $("#api-method-"+apiId).html();
	const payload = $("#api-payload-"+apiId).val();
	const params = $("#api-params-"+apiId).val();
	const token = $("#token").val();
	const url = $("#api-url").val();
	const req = url + api;
	if(params) { api += "?" + params; }
	debugAPI("new call ==["+now()+"]========> ");
	debugAPI("\tcalling ("+method+")["+api+"]");
	debugAPI("\t\t " + req);
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
	ajaxApi(method, req, payloadJson, token, true)
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
		.then(rs => {
//			console.error(rs);
			showOn("#api-rs-" + apiId, rs);
		});
}

function updateApiUrl(apiId) {
	let url = $("#api-original-"+apiId).val();
	let apiName = $("#api-endpoint-"+apiId);
	let queryVars = $("#call-api-"+apiId+" .query-var");
	queryVars.each((index, element) => {
		let el = $(element);
		let val = el.val();
		if(!val) return;
		let param = el.attr("placeholder");
		url = url.replace(':'+param, val);
	});
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
