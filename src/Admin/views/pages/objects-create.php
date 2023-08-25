<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;

$elements = AdminElements::Instance();
$elements->Header("Create Objects");

$jsFile = __DIR__."/../javascript/object-creation.js";
AdminManager::Instance()->AddJs($jsFile);

?>

<div class="container">

	<? include(__DIR__."/../actions/tables-list.php"); ?>
	<div id="obj-responses"></div>

</div>

<script type="text/javascript">
let cards = [];
function objClick(table) {
	if(cards[table]) return;
	$("#btn-"+table).addClass("active");
	cards[table] = true;
	callAction("object-create&onclose=closeViewCard&table=" + table)
		.then(rs => {
			$("#obj-responses").prepend(rs)
		});
}
function objClose(table) {
	cards[table] = false;
	$("#btn-"+table).removeClass("active");
}
function closeViewCard(el) {
	let parent = $(el).parents().eq(1);
	let id = $(parent).attr("id");
	if(!id) return;
	let table = id.split('-')[1];
	objClose(table);
}
</script>

