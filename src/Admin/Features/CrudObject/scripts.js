function newCrudObject() {
	let featureId = $("#crud-feature-id").val();
	callFeature(featureId, "Form")
		.then(rs => showOn("#container-crudobject-form", rs));
}

function editCrudObject(id) {
	let featureId = $("#crud-feature-id").val();
	callFeature(featureId, "Form", "GET", { id })
		.then(rs => showOn("#container-crudobject-form", rs));
}

function loadCrudObjectList() {
	let container = "#container-crudobject-list";
	let featureId = $("#crud-feature-id").val();
	callFeature(featureId, "List")
		.then(rs => showOn(container, rs));
}

function saveCrudObject(el) {
	let featureId = $("#crud-feature-id").val();
	let data = getFormDataFromElement(el);
	console.info(data);
	callFeature(featureId, "Save", "POST", data)
		.then((rs) => {
			rs = JSON.parse(rs);
			if(!rs.success) {
				showToast(rs.error, "Error", true);
			} else {
				let type = rs.type;
				if(type == "insert") {
					showToast("Object inserted!", "Inserted!", false);
				} else {
					showToast("Object updated!", "Data Saved", false);
				}
			}
			loadCrudObjectList();
		});

}
