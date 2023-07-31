<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\DB\Query;
use Magrathea2\DB\QueryHelper;
use Magrathea2\DB\QueryType;


use function Magrathea2\p_r;

$adminElements = AdminElements::Instance();

$magQuery = new Query();
$code = "";
$type = @$_POST["type"];
switch($type) {
	case "insert":
		$magQuery = $magQuery::Insert();
		$code.= "\$query = Magrathea2\DB\Query::Insert()";
		break;
	case "update":
		$magQuery = $magQuery::Update();
		$code.= "\$query = Magrathea2\DB\Query::Update()";
		break;
	case "delete":
		$magQuery = $magQuery::Delete();
		$code.= "\$query = Magrathea2\DB\Query::Delete()";
		break;
	case "select":
		$magQuery = $magQuery::Select();
		$code.= "\$query = Magrathea2\DB\Query::Select()";
		break;
	default:
		$code = "\$query = new Magrathea2\DB\Query();\n\$query";
		break;
}

$q = $_POST["q"];
$code .= $q;
$code .= ";";

?>

<div class="card">
	<div class="card-header">
		Query
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-12">
				<pre class="code"><?=$code?></pre>
			</div>
			<div class="col-6">
				<?
				$adminElements->Button("->SQL()", "viewSQL(this)", ["w-100", "btn-success", "m-0"]);
				echo "<br/><br/>";
				$adminElements->Button("Run SQL", "executeSQL(this)", ["w-100", "btn-primary", "m-0"]);
				?>
			</div>
			<div class="col-6">
				<div class="view-query" style="display: none;">
					<?
						eval($code);
						$qRs = $query->SQL();
					?>
					<pre class="code-light rs-run"><?=$qRs?></pre>
				</div>
			</div>
			<div class="col-12 query-run-rs"></div>
		</div>
	</div>
</div>

