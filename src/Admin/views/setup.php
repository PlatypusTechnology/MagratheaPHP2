<?php

use Magrathea2\Admin\Install;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\Start;

$admin = Start::Instance();
$title = AdminManager::Instance()->GetTitle();

?>

<!DOCTYPE html>
<html lang="en">
	<head>
	<?
		$pageTitle = $title." - First setup";
		$cssStyleFiles = ["login", "cards"];
		include("sections/meta.php");
	?>
	</head>
	<body>

		<?php
			$pageTitle = $title." - SETUP";
			include("sections/header.php");
		?>

		<div id="wrapper" class="container setup-wrapper">
			<!-- Page content wrapper-->
			<div id="page-content-wrapper">
				<div class="center">
					<? \Magrathea2\Admin\AdminManager::Instance()->PrintLogo(200); ?>
				</div>
				<div class="disclaimer">
					This is the first setup for Magrathea Admin.<br/>
					Now you will create the first user, with <i>super admin</i> permissions.
				</div>
				<?php
					$post = @$_POST;
					$showForm = true;
					if ($post && @$post["action"] === "insert_user") {
						$user = $post["admin_login"];
						$pass = $post["admin_password"];
						if(empty($user) || empty($pass)) {
							?>
							<div class="error">Email and password cannot be empty</div>
							<?
						} else {
							$adminInstall = new Install();
							$user = $adminInstall->CreateFirstUser($user, $pass);
							?>
							<div>
								User <?=$user?> created!<br/>
								Reload for Login Page<br/>
								<a href='javascript:location.reload()'>Click here to reload</a><br/>
							</div>
							<?
							$showForm = false;
						}
					}
					if ($showForm) {
						include("pages/setup/new-admin-form.php");
					}
				?>
				
			</div>
		</div>
	</body>
	<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
	<script type="text/javascript">
		<?php include("javascript/scripts.js"); ?>
		function generateRandomPassword() {
			let randompass = Math.random().toString(36).slice(-10);
			$("#admin_password").val(randompass);
			$("#admin_password_gen").val(randompass);
		}
	</script>
</html>
