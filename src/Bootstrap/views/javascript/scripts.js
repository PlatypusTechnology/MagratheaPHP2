function showOn(container, rs) {
	$(container).html(rs);
	$(container).show('slow');
}

function viewCodeFile() {
	
}

function getBootstrapUrl(){
	return window.location.href.split('?')[0];
}

function generateCode() {
	let url = "/?action=generate-code";
	$.get(url, rs => showOn("#code-gen-rs", rs));
}

function showLoading() {
	$("#loading").slideDown();
}
function hideLoading() {
	$("#loading").slideUp();
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
