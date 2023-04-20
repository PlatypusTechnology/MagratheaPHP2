<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

$object = $_GET["object"];
if(empty($object)) {
	AdminElements::Instance()->Alert("invalid object!", "danger");
	die;
}

$closeFn = @$_GET["onclose"];
if($closeFn) {
	$closeFn = $closeFn."('".$object."');";
}

?>

<div class="card">
	<div class="card-header">
		Code for <?=$object?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this); <?=$closeFn?>">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
		</div>
	</div>
</div>
