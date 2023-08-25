<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminUrls;
use Magrathea2\Admin\CodeManager;

use function Magrathea2\p_r;

	$obj = $_GET["object"];
	$codeManager = CodeManager::Instance();
	$files = $codeManager->GetFileList($obj);

	echo "<div class='row'>";
	foreach($files as $type => $f) {
		$data = $codeManager->PrepareCodeFileGeneration($type, $obj);
		?>
		<div class="col-6 mt-2">
			<b><?=$f["file-desc"]?></b><br/>
			[<?=$f["file-name"]?>]
			<?
			if($data["file-exists"]) {
				$fileViewLink = AdminUrls::Instance()->FileViewUrl($data["file-destination"]);
				echo "- <span class='warning'>File already exists</span> ";
				echo "[<a href='".$fileViewLink."'>View File</a>]";
				echo "<br/>";
				if($data["overwritable"]) {
					echo "- <span class='warning'>Existing file will be overwritten</span><br/>";
				}
			}
			?>
		</div>
		<div class="col-6 mt-2 right">
			<?
				AdminElements::Instance()->Button("Create File", "codeCreation('".$obj."', '".$type."', this);", ["btn-success", "mt-0"]);
			?>
		</div>
		<div class="col-12 code_<?=$obj?>_<?=$type?> mb-2 border-bottom">
			<pre class="code"><?=htmlentities($data["code"])?></pre>
		</div>
		<?
	}
	echo "</div>";
