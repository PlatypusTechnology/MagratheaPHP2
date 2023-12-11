<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\Features\User\AdminUserControl;
use function Magrathea2\p_r;

$elements = AdminElements::Instance();
$elements->Header("Users Feature");

$adminForm = new AdminForm();
$adminForm->SetName("user-form");
$crud = $adminForm->CRUDObject(new AdminUser(), true);

$control = new AdminUserControl();
$rs = $control->GetAll();

$featureClass = AdminManager::Instance()->GetActiveFeature();
$newUserUrl = $featureClass->GetSubpageUrl(null, [ "id" => "new" ]);

?>
<div class="container">
	<div class="card">
		<div class="card-header">
			All Users
		</div>
		<div class="card-body">
			<?
			$elements->Table(
				$rs,
				[
					[
						"title" => "#ID",
						"key" => "id"
					],
					[
						"title" => "E-mail",
						"key" => "email"
					],
					[
						"title" => "Last Online",
						"key" => "last_login"
					],
					[
						"title" => "Role",
						"key" => function($item) {
							return $item->GetRoleName();
						}
					],
					[
						"title" => "&nbsp;",
						"key" => function($item) {
							$featureClass = AdminManager::Instance()->GetActiveFeature();
							if(empty($featureClass)) return "[error]";
							$editUrl = $featureClass->GetSubpageUrl(null, [ "id" => $item->id ]);
							return '<a href="'.$editUrl.'">Edit</a>';
						}
					]
				]
			);
			?>
			<button class="btn btn-primary right" onclick="window.location.href='<?=$newUserUrl?>'">
				Add User
			</button>
		</div>
	</div>

<?
	include(__DIR__."/edit.php");

