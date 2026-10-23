<?php

	use Magrathea2\Config;
	use Magrathea2\DB\Database;
	use Magrathea2\MagratheaPHP;

	use function Magrathea2\p_r;

	$config = Config::Instance();
	MagratheaPHP::Instance()->Connect();
	$magDb = Database::Instance();

	$query = "SHOW TABLES";
	echo $query;
	$rs = $magDb->QueryAll($query);

function getColumnsOf($table) {
	$query = "SHOW COLUMNS FROM `".str_replace("`", "``", $table)."`";
	$rs = Database::Instance()->QueryAll($query);
	return $rs;
}

if(!$rs) {
	echo "<br/><span class='error'> - no tables - </span>";
}
echo "<br/><br/>";

foreach($rs as $table) { 
	$t = array_pop($table);
	$c = getColumnsOf($t);
	// names, types and defaults come from the database: escaped
	echo "<h5>".htmlspecialchars($t)."</h5>";
	echo "<table class='table table-striped'>";
	foreach($c as $row) {
		echo "<tr>";
		echo "<td>".htmlspecialchars($row["field"])."</td>";
		echo "<td>".htmlspecialchars($row["type"])."</td>";
		echo "<td>".($row["null"] == "NO" ? "" : "nullable")."</td>";
		echo "<td>".(empty($row["default"]) ? "" : "default: ".htmlspecialchars($row["default"]) )."</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br/><br/>";
} 
?>
