<?php

	$bootstrap = Magrathea2\Bootstrap\CodeManager::Instance()->Load();
	$confFile = $bootstrap->getMagratheaObjectsFile();

	?>
	<h5>Generating code</h5>
	<span><?=$confFile?></span>
	<br/>
	<?

	if(!$confFile) {
		?>
		<span class="error">no magrathea_objects.conf file</span>
		<?
		return;
	}

?>
<div class="row">
	<div class="col-12">
		<button class="btn btn-success" onclick="generateCode();">Generate Code</button>
	</div>
	<div class="col-12">
		<pre class="log-response" id="code-gen-rs" style="display: none"></pre>
	</div>
</div>
<?