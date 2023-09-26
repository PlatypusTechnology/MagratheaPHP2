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
$error = false;
$exception = null;

try {
	if($queryType === QueryType::Select) {
		$rs = Database::Instance()->QueryAll($query);
	} else {
		$rs = Database::Instance()->Query($query);
	}
} catch(Exception $ex) {
	$error = true;
	$rs = $ex;
//	print_r($ex);
}

?>

<div class="card ">
	<div class="card-header <?=($error ? "error" : "")?>">
		<?=($error ? "ERROR!" : "")?>
		Query <?=$type?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row border-bottom pb-1 mb-2">
			<div class="col-12"><pre class="code-light"><?=$query?></pre></div>
			<?
			$switchAction = ["onchange" => "switchRs(this);"];
			if($queryType === QueryType::Select) {
				echo '<div class="raw-switch">';
				$adminElements->Checkbox(null, "Raw Response", true, true, [], true, $switchAction);
				echo '</div>';
			}
			?>
		</div>
		<div class="row">
			<div class="col-12 rs-raw">
				<?
				if($error) {
					echo $rs->getMessage()."<br/><br/>";
				}
				?>
				<pre class="pre-raw"><?print_r($rs)?></pre>
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
