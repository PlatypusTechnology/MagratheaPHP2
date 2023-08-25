<?php

use Magrathea2\Admin\ObjectManager;

use function Magrathea2\p_r;

$tables = Magrathea2\Admin\AdminDatabase::Instance()->GetTables(false);

?>

<style>
.obj-cell {
	text-align: center;
	padding: 5px;
}
.obj-cell span {
	border: 1px solid var(--primary);
	font-weight: bold;
	color: var(--primary);
	display: block;
	padding: 5px 10px;
	width: 100%;
}
.obj-cell span.active {
	background-color: var(--primary);
	color: var(--white);
}
</style>

<div class="card">
	<div class="card-header">
		Tables
	</div>
	<div class="card-body">
		<div class="row">
			<?php
				foreach($tables as $t) {
					$table = $t["table_name"];
					$objClick = "objClick('".$table."')";
					echo '<div class="col-3 obj-cell pointer" onclick="'.$objClick.'"><span id="btn-'.$table.'">'.$table.'</span></div>';
				}
			?>
		</div>
	</div>
</div>
