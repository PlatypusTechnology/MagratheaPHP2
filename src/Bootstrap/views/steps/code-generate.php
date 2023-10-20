<?php

$manager = Magrathea2\Admin\ObjectManager::Instance();
$confFile = $manager->GetObjectFilePath();

	?>
	<h5>Generate Objects code</h5>
	<span><?=$confFile?></span>
	<br/>
	<?

	if(!$confFile || !$manager->DoesObjectFileExists()) {
		?>
		<span class="error">no magrathea_objects.conf file</span>
		<?
		return;
	} else {
		?>
		<div class="row">
			<div class="col-12 btn-obj-view-file">
				<pre class="code"><? $configFile = realpath($manager->GetObjectFilePath());
echo file_get_contents($configFile); ?>
				</pre>
			</div>
		</div>
		<?
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