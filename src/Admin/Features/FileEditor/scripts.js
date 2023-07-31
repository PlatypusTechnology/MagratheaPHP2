function openFile() {
	let file = $("#file").val();
	console.info("opening " + file);
	callFeature("AdminFeatureFileEditor", "View", "POST", { file })
		.then( rs => showOn('#file-editor-view', rs) );
}
