<?php

use Magrathea2\DB\Database;
use Magrathea2\DB\Query;
use Magrathea2\DB\QueryHelper;
use Magrathea2\DB\QueryType;
use Symfony\Component\VarDumper\Cloner\Data;

$adminElements = \Magrathea2\Admin\AdminElements::Instance();

$query = @$_POST["q"];
if(empty($query)) {
	$adminElements->Alert("query empty!", "danger");
	die;
}

$queryType = QueryHelper::GetQueryType($query);
$type = QueryHelper::GetTypeString($queryType);

if($queryType === QueryType::Select) {
	$rs = Database::Instance()->QueryAll($query);
} else {
	$rs = Database::Instance()->Query($query);
}

?>

<div class="card">
	<div class="card-header">
		Query <?=$type?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row border-bottom pb-1 mb-2">
			<div class="col-8"><?=$query?></div>
			<div class="col-4">
				<?
				$switchAction = ["onchange" => "switchRs(this);"];
				if($queryType === QueryType::Select) {
					$adminElements->Checkbox(null, "Raw Response", true, true, [], true, $switchAction);
				}
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-12 rs-raw">
				<pre><?print_r($rs)?></pre>
			</div>
			<div class="col-12 rs-table table-scroll" style="display: none;">
				<?
				if($queryType === QueryType::Select) {
					$adminElements->Table($rs);
				}
				?>
			</div>
		</div>
	</div>
</div>
