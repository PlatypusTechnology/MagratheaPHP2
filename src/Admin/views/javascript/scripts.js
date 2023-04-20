function showToast(message, title="Error") {
	let toast = $("#bread-pack .toast").clone();
	$(toast.find(".toast-title")).html(title);
	$(toast.find(".toast-body")).html(message);
	$(toast).appendTo("#toast-container");
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

function ajax(method, url, data) {
	showLoading();
  return new Promise(function(resolve, reject) {
    $.ajax({
      url: url,
      method: method,
      data: data,
      success: function(response) {
				hideLoading();
        resolve(response);
      },
      error: function(jqXHR, textStatus, errorThrown) {
				hideLoading();
        reject(errorThrown);
      }
    });
  });
}

function callAction(action, method="GET", data=null) {
	let url = "/"+getCurrentPage()+"?magrathea_action="+action;
	return ajax(method, url, data);
}

function callApi(object, fn, params=null) {
	let url = "/"+getCurrentPage()+"?magrathea_api="+ucfirst(object)+"&magrathea_api_method="+ucfirst(fn);
	return ajax("POST", url, params);
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
