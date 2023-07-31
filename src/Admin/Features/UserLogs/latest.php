<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminUrls;
use Magrathea2\Admin\Features\UserLogs\AdminLogControl;
use function Magrathea2\p_r;

$adminElements = AdminElements::Instance();
$control = new AdminLogControl();
$latest = $control->GetLatest();
?>

<div class="card">
	<div class="card-header">
		Latest Logs
	</div>
	<div class="card-body">
		<?
		$adminElements->Table($latest,
		[
			"user_id" => "User ID",
			"action" => "Action",
			"created_at" => "Date",
			[
				"title" => "",
				"key" => 	function($item) {
					$viewAction = "javascript:detailLog('".$item->id."')";
					return '<a href="'.$viewAction.'">View</a>';
				}
			]
		]);
		?>
	</div>
</div>


