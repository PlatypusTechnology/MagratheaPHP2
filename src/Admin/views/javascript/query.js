function setType() {
	let el = "#magq-type";
	let type = $(el).val();
	let text = $(el + " option:selected").text();
	let init = "";
	console.info(type);
	if(type && type != "_") {
		init = "MagratheaQuery()" + text;
	} else {
		init = "new MagratheaQuery() ";
	}
	$("#pre-code").val(init);
}
function getData() {
	let rs = {};
	let type = $("#magq-type").val();
	if(type) { rs["type"] = type; }
	rs["q"] = $("#query").val();
	return rs;
}
function loadSQL() {
	let container = "#query-responses";
	let data = getData();
	callAction("query-creator", "POST", data)
		.then(rs => addTo(container, rs, true));
}
function viewSQL(el) {
	let container = $(el).parents().eq(2).find(".view-query");
	$(container).slideDown("slow");
}
function executeSQL(el) {
	let container = $(el).parents().eq(2).find(".view-query pre.rs-run");
	let q = $(container).text();
	let containerRs = $(el).parents().eq(3).find(".query-run-rs");
	console.info("runnning ", q);
	if(!q) return;
	callAction("query-run-card", "POST", { q })
		.then((rs) => addTo(containerRs, rs, false, true));
}
function switchRs(el) {
	let cardBody = $(el).parents().eq(3);

	let tableRs = $(cardBody).find('.rs-table');
	let rawRs = $(cardBody).find('.rs-raw');
	$(tableRs).slideToggle('slow');
	$(rawRs).slideToggle('slow');
}
