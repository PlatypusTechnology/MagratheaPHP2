<?php

use Magrathea2\Admin\AdminUrls;
use Magrathea2\Admin\Features\User\AdminUser;

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
					"type" => "button",
					"class" => ["btn-primary", "w-100", "change-pwd-btn"],
					"name" => "Change Password",
					"action" => "changePassword();",
					"size" => "col-3 offset-3",
				],
				[
					"type" => "save-button",
					"size" => "col-3",
				],
			],
			$user
		)->Print();
		?>
	</div>
</div>
	<?

	$changePasswordUrl = AdminUrls::Instance()
		->GetFeatureUrl("AdminFeatureUser", "ChangePassword", [ "id" => $user->id ]);

}
?>

</div>

<script type="text/javascript">
function DeleteUser(user_id) {
	console.log("deleting " + user_id);
}
function togglePassword(el) {
	$(el).slideUp();
}
function changePassword() {
	window.location.href='<?=$changePasswordUrl?>';
}
</script>
