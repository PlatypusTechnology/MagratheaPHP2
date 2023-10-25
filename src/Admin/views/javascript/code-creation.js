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
		});
}

function folderCreation(object, el) {
	let rsContainer = ".folder_rs_"+object;
	callApi("Objects", "CreateFolder", { object })
		.then(rs => {
			let html = '';
			let type = rs.data.type;
			if(type == "feature") {
				html += getPrintedEl("App", rs.data["app"]);
				html += getPrintedEl("Object", rs.data["object"]);
				html += getPrintedEl("Base Code", rs.data["base"]);
			} else if(type == "mvc") {
				html += getPrintedEl("App", rs.data["app"]);
				html += getPrintedEl("Models", rs.data["models"]);
				html += getPrintedEl("Models Base", rs.data["models-base"]);
				html += getPrintedEl("Controls", rs.data["controls"]);
				html += getPrintedEl("Controls Base", rs.data["controls-base"]);
			} else {
				html += "invalid structure type: ["+type+"]";
			}
			$(rsContainer).html(html);
			let codeContainer = ".code_create_"+object;
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
