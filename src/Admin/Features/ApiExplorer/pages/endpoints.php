<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;

use function Magrathea2\getClassNameOfClass;
use function Magrathea2\p_r;

$elements = AdminElements::Instance();

$feature = AdminManager::Instance()->GetActiveFeature();
$api = $feature->apiName;
$url = $feature->apiUrl;

//p_r($endpoints);

?>

<style>
.api-row {
	margin: 5px 0;
}
.api-name {
	font-weight: bold;
	font-family: monospace;
	color: var(--primary);
	text-align: right;
	border-bottom: 1px solid var(--primary);
	padding-top: 10px;
	margin-top: 5px;
}
.api-method {
	padding-top: 10px;
}
.payload-text {
	margin-bottom: 10px;
}
.api-rs {
	min-height: 300px;
}
</style>

<div class="card">
	<div class="card-header">
		<?=$api?> endpoints
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-12 mb-2">
				<?
				$elements->Input("disabled", "api-url", "API Url", $url);
				?>
			</div>
		</div>
		<?php
		$apiId = 1;
		foreach($endpoints as $ends) {
			foreach($ends as $end) {
			?>
			<div class="row">
				<div class="col-6 api-name" id="api-<?=$apiId?>"><?=$end["url"]?></div>
				<div class="col-2 api-method" id="api-method-<?=$apiId?>"><?=$end["method"]?></div>
				<div class="col-2 no-padding" id="api-btn-<?=$apiId?>">
					<? $elements->Button("Call", "loadApi(".$apiId.");", ["btn-primary", "no-margin"]); ?>
				</div>
				<div class="col-2 no-padding" id="api-btn-hide-<?=$apiId?>" style="display: none;">
					<? $elements->Button("Hide", "hideApi(".$apiId.");", ["btn-warning", "no-margin"]); ?>
				</div>
				<div class="col-2 api-auth" id="api-auth-<?=$apiId?>">
					<?=($end["auth"] ? "auth: [".$end["auth"]."]" : "[public api]")?>
				</div>
			</div>
			<div class="row api-row" id="call-api-<?=$apiId?>" style="display: none;">
				<div class="col-2">
					<?
					$elements->Input("hidden", "api-original-".$apiId, false, $end["url"]);
					foreach($end["params"] as $param) {
						$attr = [
							"onchange" => "updateApiUrl(".$apiId.", '".$param."', this)"
						];
						$elements->Input("text", "api-".$apiId."-".$param, false, null, null, null, $param, $attr);
						echo "<br/>";
					}
					if ($end["method"] == "POST" || $end["method"] == "PUT") {
						$elements->Textarea("api-payload-".$apiId, "Payload", null, ["w-100", "payload-text"]);
					}
					$elements->Button($end["method"], "executeApi(".$apiId.")", ["btn-success", "w-100", "no-margin"]);
					?>
				</div>
				<div class="col-10">
					<pre class="code-light api-rs" id="api-rs-<?=$apiId?>"></pre>
				</div>
			</div>
			<?
			$apiId ++;
			}
		}
//		p_r($endpoints);
		?>
	</div>
</div>