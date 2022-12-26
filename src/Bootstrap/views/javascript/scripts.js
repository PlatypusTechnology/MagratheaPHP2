function showOn(container, rs) {
	$(container).html(rs);
	$(container).show('slow');
}


function generateCode() {
	let url = "/?action=generate-code";
	$.get(url, rs => showOn("#code-gen-rs", rs));
}
