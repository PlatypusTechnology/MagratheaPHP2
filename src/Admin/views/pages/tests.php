<?php

use function Magrathea2\p_r;

$testManager = \Magrathea2\Tests\TestsManager::Instance();
$adminElements = \Magrathea2\Admin\AdminElements::Instance();

$pageTitle = "Tests";
$adminElements->Header($pageTitle);

$tests = $testManager->GetTests();

?>
<style>
.ul-files {
	margin-left: 20px;
}
</style>


<div class="container">

	<div class="card">
		<div class="card-header">
			Instalation
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-12">
					Composer <b>Codeception</b> Installation: <br/>
					<pre class="code">composer require "codeception/codeception" --dev</pre>

				</div>
			</div>
		</div>
	</div>


	<?
	$i=0;
	foreach($tests as $test) {
		$i++;
		$resultContainer = "test-results-".$i;
		?>
		<div class="card">
			<div class="card-header">
				<?=$test["name"]?>
				<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-4">
						<? 
						foreach($test["folders"] as $folder) {
							$filesContainer = "files-folder-".$i;
							$folderName = $testManager::TestFolderPrint($folder);
							$folderClick = 'viewFolder("'.$folder.'", "'.$i.'");';
							$adminElements->Button('+', $folderClick, 'btn-action btn-primary');
							?>
								<b><?=$folderName?></b>
								<div id="<?=$filesContainer?>"></div>
							<?
							$adminElements->Button("Run ".$folderName, "callRunFolder('".$folder."', '#".$resultContainer."');", 'btn-primary w-100');
						}
						?>
					</div>
					<div class="col-8" id="<?=$resultContainer?>">

					</div>
				</div>
			</div>
		</div>
		<?	
	}
	?>

</div>

<script type="text/javascript">
	function callTestRun(data, containerShow) {
		return ajax('POST', "?magrathea_subpage=test-run", data)
			.then(rs => showOn(containerShow, rs) );
	}
	function callRunFolder(folder, containerShow) {
		callTestRun({ folder }, containerShow);
	}
	function callRunFile(file, containerShow) {
		callTestRun({ file }, containerShow);
	}
	function callRunFunction(file, fn, containerShow) {
		callTestRun({ file, fn }, containerShow);
	}
	function viewFolder(folder, containerId) {
		let resultContainer = 'test-results-'+containerId;
		callAction("test-view-folder", "POST", { folder, container: resultContainer })
			.then(rs => showOn('#files-folder-'+containerId, rs) );
	}
	function viewFile(file, resultContainer, containerShow) {
		callAction("test-view-methods", "POST", { file, container: resultContainer })
			.then(rs => { showOnVanilla(containerShow, rs, true) });
	}
	function expandFunctions(id) {
		console.info('expanding ' + id);
		$(id).slideDown('slow');
	}
</script>

