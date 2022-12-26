function testConnection() {
	let url = "/?action=database-test";
	$.get(url, rs => showOn("#ajax-response", rs));
}

function getTables() {
	let url = "/?action=show-tables";
	$.get(url, rs => showOn("#ajax-response2", rs));
}

function runQuery() {
	let query = $("#query").val();
	let url = "/?action=run-query";
	$.post(url, { q: query }, rs => showOn("#query-response", rs));
}

function sendCreateToExecute(table) {
	$("#query").val($("#create-"+table).html());
	$("html, body").animate({ scrollTop: 0 }, "slow");
}