function getForm($form) {
	var unindexed_array = $form.serializeArray();
	var indexed_array = {};
	$.map(unindexed_array, function(n, i){
		indexed_array[n['name']] = n['value'];
	});
	return indexed_array;
}

function saveDatabaseInfo() {
	var formData = getForm($("#dbInfoForm"));
	let postAction = "/?action=save-config";
	ajax("POST", postAction, formData).then(rs => {
		$("#ajax-response").html(rs);
		$("#ajax-response").show('slow');
	});
}
