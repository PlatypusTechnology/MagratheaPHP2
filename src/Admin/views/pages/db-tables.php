<?php

use function Magrathea2\p_r;

$dbManager = Magrathea2\Admin\AdminDatabase::Instance();
$adminElements = \Magrathea2\Admin\AdminElements::Instance();

$adminElements->Header("Database");

?>

<div class="container">
	<div class="card">
		<div class="card-header">
			Tables
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-12">
					<?
					$adminElements->Checkbox(
						"all-tables", "Show All Tables", 
						true, false, [], true,
						["onchange" => "loadTables();"]);
					?>
				</div>
				<div class="col-3 table-list border-end p-0" id="show-tables">
				</div>
				<div class="col-9">
					<div class="form-group">
						<label for="query">Query</label>
						<textarea class="form-control" id="query"></textarea>
					</div>
					<div class="right">
					<?
					$goClick = "RunQuery();";
					$adminElements->Button("Execute", $goClick, ["btn-success"]);
					?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="query-responses"></div>

</div>

<script type="text/javascript">
function loadTables() {
	let showAll = jQuery("#all-tables").is(":checked");
	callAction("tables-view&show-all=" + showAll)
		.then(rs => showOn("#show-tables", rs));
}
function tableClick(table) {
	let query = "SELECT * FROM " + table + " LIMIT 50";
	$("#query").val(query);
}
function RunQuery() {
	let q = $("#query").val();
	if(!q) return;
	callAction("query-run-card", "POST", { q })
		.then((rs) => {
			$("#query-responses").prepend(rs);
		});
}

function switchRs(el) {
	let cardBody = $(el).parents().eq(3);

	let tableRs = $(cardBody).find('.rs-table');
	let rawRs = $(cardBody).find('.rs-raw');
	$(tableRs).slideToggle('slow');
	$(rawRs).slideToggle('slow');
}

afterLoad().then( loadTables );
</script>

