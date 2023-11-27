function editConfig(configId) {
	callFeature("AdminFeatureAppConfig", "View", "GET", {"id": configId})
		.then(rs => showOn("#config-data-form", rs));
}
newConfig = () => editConfig("new");

function updateAppConfigList() {
	callFeature("AdminFeatureAppConfig", "List")
		.then(rs => showOn("#app-config-list", rs, true));
}

function copyExport() {
	let copyText = document.getElementById("exportStr").textContent;
  let textArea = document.createElement('textarea');
  textArea.textContent = copyText;
  document.body.append(textArea);
  textArea.select();
  document.execCommand("copy");
	console.info("copying", copyText);
	showToast("App Config data copied to clipboard", "Copied!");
	textArea.remove();
}

function importAppConfig() {
	let importData = $("#config-import").val();
	console.info("importing", importData);
	callFeature("AdminFeatureAppConfig", "Import", "POST", { "data": importData })
		.then(rs => showOn("#migration-rs", rs, true));
}
