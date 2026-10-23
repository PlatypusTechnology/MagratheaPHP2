<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\AdminUrls;
use Magrathea2\Admin\CodeManager;

use function Magrathea2\p_r;

	$obj = @$_GET["object"];
	if(empty($obj) || !is_string($obj)) {
		AdminElements::Instance()->Alert("invalid object!", "danger");
		return;
	}
	// GET parameters are links an admin can be sent: escaped
	$objHtml = htmlspecialchars($obj);
	$js = fn($value) => htmlspecialchars(json_encode($value));
	$codeManager = CodeManager::Instance();
	$files = $codeManager->GetFileList($obj);
	$hasFileEditor = AdminManager::Instance()->GetFeature("AdminFeatureFileEditor") !== null;

	echo "<div class='row'>";
	foreach($files as $type => $f) {
		$data = $codeManager->PrepareCodeFileGeneration($type, $obj);
		?>
		<div class="col-6 mt-2">
			<b><?=htmlspecialchars($f["file-desc"])?></b><br/>
			[<?=htmlspecialchars($data["file-destination"])?>]<br/>
			<?php
			if($data["file-exists"]) {
				echo "- <span class='warning'>File already exists</span> ";
				if($hasFileEditor) {
					$fileViewLink = AdminUrls::Instance()->FileViewUrl($data["file-destination"]);
					echo "[<a href='".htmlspecialchars($fileViewLink, ENT_QUOTES)."'>View File</a>]";
				}
				echo "<br/>";
				if($data["overwritable"]) {
					echo "- <span class='warning'>Existing file will be overwritten</span><br/>";
				}
			}
			?>
		</div>
		<div class="col-6 mt-2 right">
			<?php
				AdminElements::Instance()->Button("Create File", "codeCreation(".$js($obj).", ".$js($type).", this);", ["btn-success", "mt-0"]);
			?>
		</div>
		<div class="col-12 code_<?=$objHtml?>_<?=htmlspecialchars($type)?> mb-2 border-bottom">
			<pre class="code"><?=htmlentities($data["code"])?></pre>
		</div>
		</div>
		<?php
	}
	echo "</div>";
