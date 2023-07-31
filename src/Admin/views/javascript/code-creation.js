function saveCodeGenerationConfig(el) {
	let data = getFormDataFromElement(el);
	callApi("AdminConfig", "SaveConfig", data)
		.then(rs => {
			if(!rs) {
				showToast("Couldn't save code configuration.");
			}
			let data = rs.data;
			if(data['success']) {
				$(".message-container").slideUp("slow");
			}
//			console.info(data);
		});
}

function folderCreation(object, el) {
	let rsContainer = ".folder_rs_"+object;
	let codeContainer = ".code_create_"+object;
	callApi("Objects", "CreateFolder", { object })
		.then(rs => {
			let html = '';
			html += getPrintedEl("Base", rs.data["base"]);
			html += getPrintedEl("Features", rs.data["features"]);
			html += getPrintedEl("Object", rs.data["object"]);
			$(rsContainer).html(html);
			$(codeContainer).slideDown("slow");
			if(el) {
				$(el).hide("slow");
			}
		});
}

function codeCreation(object, type, el=null) {
	showLoading();
	callApi("Objects", "CreateCode", { object, type })
		.then(rs => {
			console.info(rs);
			let data = rs.data;
			let html = '';
			data.forEach(f => {
				let rsContainer = ".code_"+object+"_"+f.type;
				if(f.success) {
					html += "<span class='success'>File "+(f.overwrite ? "updated" : "created")+" at ["+f["file-name"]+"]</span>";
				} else {
					html += "<span class='error'>ERROR! "+f.error+"</span>";
				}
				$(rsContainer).html(html);
				$(rsContainer).slideDown("slow");
			});
		});

}

function getPrintedEl(title, data) {
	var htmlString = '';
	htmlString = "<h5>"+title+"</h5>";
	for (var key in data) {
		if (data.hasOwnProperty(key)) {
			htmlString += '<b>' + key + '</b>: ' + data[key] + '<br>';
		}
	}
	return htmlString+"<br/>";
}

function viewCodeGen(object) {
	let container = ".code_create_rs_" + object;
	callAction("code-gen-view&object=" + object)
		.then(rs => showOn(container, rs, false));
}
