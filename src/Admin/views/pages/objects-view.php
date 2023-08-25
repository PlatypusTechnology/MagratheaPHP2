<?php

\Magrathea2\Admin\AdminElements::Instance()->Header("Objects");

?>

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
	callAction("object-detail&onclose=closeViewCard&object=" + obj)
		.then(rs => {
			$("#obj-responses").prepend(rs)
		});
}
function objClose(obj) {
	cards[obj] = false;
	$("#btn-"+obj).removeClass("active");
}
function closeViewCard(el) {
	let parent = $(el).parents().eq(1);
	let id = $(parent).attr("id");
	if(!id) return;
	let obj = id.split('-')[1];
	objClose(obj);
}
</script>

