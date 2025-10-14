<?php

use Magrathea2\DB\Database;
use Magrathea2\MagratheaPHP;

	MagratheaPHP::Instance()->Connect();
	$adminInstall = new Magrathea2\Admin\Install();
	$db = Database::Instance()->getDatabaseName();
	$folder = MagratheaPHP::Instance()->appRoot;
	$adminCode = $adminInstall->GetAdminCode();

?>

<h3>Magrathea Bootstrap Tools</h3>

<?php
	if(!$db) {
		?>
	<div class="row">
		<div class="col-12">
			<div class="error">
				Database is Empty. For accesing tools, it's necessary to start db!
			</div>
		</div>
	</div>
		<?php
	}
?>

<div class="row mb-2">
	<div class="col-12"><hr/></div>
	<div class="col-12">
		<h4>Reset User Password</h4>
		<button class="btn btn-primary" onclick="getUsers();">View Users</button>
	</div>
	<div class="col-12" id="reset-user">
	</div>

	<div class="col-12"><hr/></div>
</div>

<script type="text/javascript">
	function getUsers() {
		let url = getBootstrapUrl()+"?action=users-get";
		ajax("GET", url).then((rs) => {
			showOn("#reset-user", rs);
		});
	}

	function resetUser(user_id) {
		let url = getBootstrapUrl()+"?action=user-reset";
		const pass = getRandomPassword();
		$("#pass_" + user_id).val(pass);
		let payload = {
			id: user_id,
			new_pass: pass
		};
		ajax("POST", url, payload).then((rs) => {
			alert("password updated to " + pass);
		});
	}

	function getRandomPassword() {
		return Math.random().toString(36).slice(-10);
	}
</script>
