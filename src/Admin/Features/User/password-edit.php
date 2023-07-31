<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;

use function Magrathea2\p_r;

$adminElements = AdminElements::Instance();
$adminElements->Header("Change Password");

?>

<div class="container">

<?
if(!empty(@$saved)) {
	if($saved["success"]) {
		$adminElements->Alert("Password successfully changed", "success", true);
	} else {
		$errorMsg = @$saved["error"] ? $saved["error"] : "Unknwown error changing password";
		$adminElements->Alert($errorMsg, "danger", true);
	}
}

$adminForm = new AdminForm();
	?>
	<div class="card card-form">
		<div class="card-header">
			Change password [<b><?=$user->email?></b>]
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<?
			$adminForm
				->SetName("pwd-change")
				->Build(
				[
					[
						"type" => "hidden",
						"size" => "col-12",
						"key" => "id",
						"name" => "id",
					],
					[
						"type" => "text",
						"name" => "New Password",
						"size" => "col-6 pwd-row",
						"key" => "new_password",
						"attributes" => [ "id" => "new_password" ],
					],
					[
						"type" => "button",
						"name" => "Randomize",
						"class" => ["btn-primary", "w-100"],
						"size" => "col-3 pwd-row",
						"action" => "randomPassword();",
					],
					[
						"type" => "button",
						"name" => "Update Password",
						"class" => ["btn-success", "w-100"],
						"size" => "col-3 pwd-row",
						"action" => "savePassword();",
					],
				],
				[
					"id" => $user->id
				]
			)->Print();
			?>
		</div>
	</div>
</div>

<script type="text/javascript">
function getRandomPassword(len) {
	var charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*()-_=+;:,.?';
	var password = '';
	for (var i = 0; i < len; i++) {
		var randomIndex = Math.floor(Math.random() * charset.length);
		password += charset[randomIndex];
	}

	return password;
}
function randomPassword() {
	let pass =getRandomPassword(15);
	$("#new_password").val(pass);
}
function savePassword() {
	let form = getFormDataFromElement($("#pwd-change"));
	callApi("AdminUser", "ChangePassword", form)
		.then(rs => {
			if(!rs.success) {
				showToast("error updating password", "Error!", true);
				return;
			}
			let data = rs.data;
			console.info(data);
			if(!data.success) {
				showToast(data.error || "error updating password", "Error!", true);
			} else {
				showToast("Password updated", "Success!");
			}
		});
}
</script>
