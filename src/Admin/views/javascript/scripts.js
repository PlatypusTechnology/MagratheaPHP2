function showToast(message, title="Error", isError=false) {
	let toast = $("#bread-pack .toast").clone();
	$(toast.find(".toast-title")).html(title);
	$(toast.find(".toast-body")).html(message);
	$(toast).appendTo("#toast-container");
	if(isError) {
		$(toast).addClass("toast-error");
	}
	$(toast).addClass("show");
	window.scrollTo(0,0);
}
function closeToast(el) {
	let toast = $(el).parents().eq(1);
	$(toast).removeClass("show");
}

function closeCard(element) {
	let card = $(element).parent().parent();
	$(card).slideUp('slow');
}

function closeAlert(element) {
	let card = $(element).parent();
	$(card).slideUp('slow');
}

function showOn(container, rs, debug=false) {
	if(debug) {
		console.info("["+container+"]", rs);
	}
	$(container).html(rs);
	$(container).show('slow');
}
function addTo(container, rs, first=true, debug=false) {
	if(debug) {
		console.info("["+container+"]", rs);
	}
	if(first) {
		$(container).prepend(rs);
	} else {
		$(container).append(rs);
	}
}
function showOnVanilla(container, rs, debug=false) {
	if(debug) {
		console.info("["+container+"]", rs);
	}
	document.getElementById(container).innerHTML = rs;
}
function showLoading() {
	$("#loading").slideDown();
}
function hideLoading() {
	$("#loading").slideUp();
}

// as presented by Google's Bard
function arrayToObject(array) {
	return array.reduce((obj, item) => ({...obj, [item]: item}), {});
}

function getCurrentPage() {
	var currentPage = window.location.pathname.split('/').pop();
	if (currentPage.includes('.php')) {
		return currentPage;
	} else {
		return '';
	}
}

function ucfirst(string) {
	return string.charAt(0).toUpperCase() + string.slice(1);
}

function ajax(method, url, payload, authorization, debug=false) {
	showLoading();
	if(debug) console.info("Calling ["+method+"]"+url+" with payload", payload);
	return new Promise(function(resolve, reject) {
		switch(method.toUpperCase()) {
			case "POST":
				$.post(url, payload, (rs) => {
					hideLoading(); resolve(rs);
				})
				break;
			case "GET":
				if(payload != null) {
					let queryString = Object.entries(payload)
					.map(([key, value]) => encodeURIComponent(key) + '=' + encodeURIComponent(value))
					.join('&');
					url = url + '&' + queryString;
				}
			default:
				let ajaxData = {
					url: url,
					method,
					type: method,
					data: payload,
					processData: false,
					success: function(response) {
						hideLoading();
						resolve(response);
					},
					error: function(jqXHR, textStatus, errorThrown) {
						let err = {
							"status": textStatus,
							"error": errorThrown,
						};
						if(jqXHR && jqXHR.responseJSON) {
							err["data"] = jqXHR.responseJSON;
						}
						hideLoading();
						console.error("error!" , err);
						reject(err);
					}
				};
				if(authorization) {
					ajaxData["beforeSend"] = (xhr) => { 
						xhr.setRequestHeader('Authorization', "Basic " + authorization);
					};
				}
				if(debug) console.info("ajaxData:", ajaxData);
				$.ajax(ajaxData);
		}
  });
}

function callAction(action, method="GET", data=null) {
	let url = "/"+getCurrentPage()+"?magrathea_subpage="+action;
	return ajax(method, url, data);
}

function callApi(object, fn, params=null) {
	let url = "/"+getCurrentPage()+"?magrathea_api="+ucfirst(object)+"&magrathea_api_method="+ucfirst(fn);
	return ajax("POST", url, params);
}

function callFeature(feature, action=null, method="GET", data=null) {
	let url = "/"+getCurrentPage()+"?magrathea_feature="+feature;
	if (action) {
		url += "&magrathea_feature_action=" + action;
	}
	return ajax(method, url, data);
}

function toggleCol(el, col) {
	let cardBody = $(el).parents().eq(2);
	let rawContainer = $(cardBody).find(col);
	$(rawContainer).slideToggle("slow");
}

function getFormDataFromElement(el) {
	let form = el.closest('form')
	if(!form) return;
	let data = $(form)
		.serializeArray()
		.reduce((json, { name, value }) => {
			json[name] = value;
			return json;
		}, {});
	return data;
}

function now() {
	let n = new Date();
	return n.toISOString().replace('T', ' ');
}
