<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\Models\AdminUser;
use Magrathea2\Admin\Models\AdminUserControl;
use function Magrathea2\p_r;

$pageTitle = "Users";
include(__DIR__."/../sections/header.php");

$elements = AdminElements::Instance();

$adminForm = new AdminForm();
$adminForm->SetName("user-form");
$crud = $adminForm->CRUDObject(new AdminUser(), true);

$control = new AdminUserControl();
$rs = $control->GetAll();

$newUserUrl = \Magrathea2\Admin\AdminUrls::Instance()
	->GetPageUrl("users", null, [ "id" => "new" ]);

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
							$editUrl = \Magrathea2\Admin\AdminUrls::Instance()
								->GetPageUrl("users", null, [ "id" => $item->id ]);
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
	if(@$_POST["magrathea-submit"] === "delete") {
		die;
	}
	if(@$_GET["id"]) {
		$id = $_GET["id"];
		if ($id === "new") {
			$user = new AdminUser();
		} else {
			$user = new AdminUser($id);
		}
		$roles = $user->GetRoles();
		?>
	<div class="card card-form">
		<div class="card-header">
			Editing <b><?=$user->email?></b>
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<?
			$adminForm->Build(
				[
					[
						"name" => "#ID",
						"key" => "id",
						"type" => "disabled",
						"size" => "col-2",
					],
					[
						"name" => "E-mail",
						"key" => "email",
						"type" => "text",
						"size" => "col-6",
					],
					[
						"name" => "Role",
						"key" => "role_id",
						"type" => $roles,
						"size" => "col-4",
					],
					[
						"type" => "delete-button",
						"size" => "col-3",
					],
					[
						"type" => "save-button",
						"size" => "col-3 offset-6",
					],
				],
				$user
			)->Print();
			?>
		</div>
	</div>
		<?
	}
?>

</div>

<script type="text/javascript">
	function DeleteUser(user_id) {
		console.log("deleting " + user_id);
	}
</script>
