<?php

use function Magrathea2\p_r;

$dbManager = Magrathea2\Admin\AdminDatabase::Instance();
$adminElements = \Magrathea2\Admin\AdminElements::Instance();

$pageTitle = "Magrathea Query Tester [WIP]";
include(__DIR__."/../sections/header.php");

?>
<style>
	.magq-fn {
		text-align: right;
		font-family: monospace;
		margin-bottom: 10px;
	}
	input.mono {
		font-family: monospace;
	}
</style>

<div class="container">
	<div class="card">
		<div class="card-header">
			Basic documentation
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body magq-documentation">
			<div class="row">
				<div class="col-4 magq-fn">::Select($select="")</div>
				<div class="col-8">Returns a <b>QuerySelect</b></div>

				<div class="col-4 magq-fn">::Insert()</div>
				<div class="col-8">Returns a <b>QueryInsert</b></div>

				<div class="col-4 magq-fn">::Update()</div>
				<div class="col-8">Returns a <b>QueryUpdate</b></div>

				<div class="col-4 magq-fn">::Delete()</div>
				<div class="col-8">Returns a <b>QueryInsert</b></div>

				<div class="col-4 magq-fn">->Table($tableName)</div>
				<div class="col-8">Sets the table</div>

				<div class="col-4 magq-fn">->Object($object) or ->Obj($object)</div>
				<div class="col-8">Sets the object</div>

				<div class="col-4 magq-fn">->Fields($fields)</div>
				<div class="col-8">String or array of fields to be selected</div>

				<div class="col-4 magq-fn">->SelectObj($object)</div>
				<div class="col-8">Will select all fields from object</div>

				<div class="col-4 magq-fn">->Join($tableName)</div>
				<div class="col-8">Sets the query join</div>

				<div class="col-4 magq-fn">->HasOne($object, $field)</div>
				<div class="col-8">INNER JOIN [$object's name] ON [$field relation]</div>

				<div class="col-4 magq-fn">->HasMany($object, $field)</div>
				<div class="col-8">INNER JOIN [$object's name] ON [$field relation]</div>

				<div class="col-4 magq-fn">->BelongsTo($object, $field)</div>
				<div class="col-8">INNER JOIN [$object's name] ON [$field relation]</div>

				<div class="col-4 magq-fn">->Inner($table, $clause)</div>
				<div class="col-8">INNER JOIN [$table] ON [$clause]</div>

				<div class="col-4 magq-fn">->Where($wheres, $condition="AND")</div>
				<div class="col-8">Sets the Where condition (array or string)</div>

				<div class="col-4 magq-fn">->OrderBy($o) or ->Order($o)</div>
				<div class="col-8">Sets the order</div>

				<div class="col-4 magq-fn">->Limit($l)</div>
				<div class="col-8">Limit to $l rows</div>

				<div class="col-4 magq-fn">->GroupBy($g)</div>
				<div class="col-8">Groups by field $g</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			Magrathea Query Creator
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-4 col-form-padding">
					MagratheaQuery
				</div>
				<div class="col-4">
					<?
					$adminElements->Select(
						"magq-type", "Type", 
						[
							"_" => " ",
							"select" => "::Select()",
							"insert" => "::Insert()",
							"update" => "::Update()",
							"delete" => "::Delete()",
						],
						null, null, "(optional)",
						[
							"onchange" => "setType(this)",
						]
					);
					?>
				</div>
				<div class="col-4">
					<?
					$adminElements->Button("View SQL", "loadSQL()", ["btn-success"]);
					?>
				</div>
			</div>
			<div class="row mt-2">
				<div class="col-12">
					<?
					$val = "new MagratheaQuery()";
					$adminElements->Input("disabled", "pre-code", false, $val, "mono");
					$adminElements->Textarea("query", false, "", "code", "mag-query", "->...");
					?>
				</div>
			</div>
		</div>
	</div>

	<div id="query-responses"></div>
</div>

<script type="text/javascript">
<?
	include(__DIR__."/../javascript/query.js");
?>
</script>
