<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminUrls;

$elements = AdminElements::Instance();
$elements->Header("User Logs");

?>
<style>
<?
	include("style.css");
?>
</style>

<div class="container">
	<? include("latest.php"); ?>
	<div id="log-rs"></div>
</div>

<script type="text/javascript">
<?
	include("scripts.js");
?>
</script>
