<?php
$allTables = @$_GET["show-all"];
$showAll = ($allTables == "true");

$tables = Magrathea2\Admin\AdminDatabase::Instance()->GetTables($showAll);
?>
<div class="list-group list-group-flush bg-light">
<?php
	foreach($tables as $t) {
		$tableClick = "tableClick('".$t['table_name']."')";
		echo '<a class="list-group-item pointer" onclick="'.$tableClick.'">'.$t['table_name'].'</a>';
	}
?>
</div>


