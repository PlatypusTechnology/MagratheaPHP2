function testConnection() {
	let url = "/?action=database-test";
	ajax("GET", url).then(rs => showOn("#ajax-response", rs));
}

function getTables() {
	let url = "/?action=show-tables";
	ajax("GET", url).then(rs => showOn("#ajax-response2", rs));
}

function runQuery() {
	let query = $("#query").val();
	let url = "/?action=run-query";
	ajax("POST", url, { q: query }).then(rs => showOn("#ajax-response", rs));
}

function sendCreateToExecute(table) {
	$("#query").val($("#create-"+table).html());
	$("html, body").animate({ scrollTop: 0 }, "slow");
}