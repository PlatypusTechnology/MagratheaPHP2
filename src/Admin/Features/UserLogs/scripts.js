function detailLog(logId) {
	callFeature("AdminFeatureUserLog", "Detail", "POST", { id: logId })
		.then(rs => addTo($("#log-rs"), rs, true, true));
}
