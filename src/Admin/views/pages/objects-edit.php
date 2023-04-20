<?php

$pageTitle = "Edit Objects";
include(__DIR__."/../sections/header.php");

?>

<style>
.relation-form {
	border-bottom: 1px solid var(--bs-card-border-color);
	padding: 20px 5px;
}
.relation-form:last-of-type {
	border-bottom: 0;
}
</style>

<div class="container">
	
	<? include(__DIR__."/../actions/object-list.php"); ?>
	
	<div id="obj-responses"></div>

</div>

<script type="text/javascript">
let cards = [];
function objClick(obj) {
	if(cards[obj]) return;
	$("#btn-"+obj).addClass("active");
	cards[obj] = true;
	callAction("object-edit&onclose=closeEditCard&object=" + obj)
		.then(rs => {
			$("#obj-responses").prepend(rs)
		});
}
function objClose(obj) {
	cards[obj] = false;
	$("#btn-"+obj).removeClass("active");
}
function closeEditCard(el) {
	let parent = $(el).parents().eq(1);
	let id = $(parent).attr("id");
	if(!id) return;
	let obj = id.split('-')[1];
	objClose(obj);
}
function viewObject(name) {
	let container = $("#object-view-"+name);
	callAction("object-detail&object="+name)
		.then(rs => showOn(container, rs));
}
<?php include(__DIR__."/../javascript/object-creation.js"); ?>
</script>

