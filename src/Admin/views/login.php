<?php

use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\AdminUsers;

$admin = AdminManager::Instance();

if(count($_POST) > 0) {
	switch ($_POST["action"]) {
		case "login_user":
			try {
				$adminUsersControl = AdminUsers::Instance();
				$login = $adminUsersControl->Login($_POST["admin_login"], $_POST["admin_password"]);
				if(!@$login) {
					$loginErrorMsg = "Error on login";
				} else {
					if(!$login["success"]) {
						$loginErrorMsg = $login["message"];
					} else {
						$url = strtok($_SERVER['REQUEST_URI'], '?');
						header('Location: ' . $url);
						exit;
					}
				}
			} catch(Exception $ex) {
				$loginErrorMsg = $ex->getMessage();
			}
			break;
		case "insert_user":
			$loginErrorMsg = "User inserted, you can login now";
			$loginEmail = $_POST["admin_login"];
			break;
		default:
			$loginErrorMsg = "Unknown Action";
			break;
	}
}

?>

<!DOCTYPE html>
<html lang="en">
	<head>
	<?
		$pageTitle = $admin->GetTitle()." - Login";
		$cssStyleFiles = ["login"];
		include("sections/meta.php");
	?>
	
	</head>
	<body>

		<?php
			include("sections/header.php");
		?>

		<div id="wrapper" class="container setup-wrapper">
			<!-- Page content wrapper-->
			<div id="page-content-wrapper">
				<?
				if ( isset($loginErrorMsg) ) {
					echo '<div class="error">'.$loginErrorMsg.'</div>';
				}
				?>
				<? include("pages/setup/login-form.php"); ?>
			</div>
		</div>
	</body>
	<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
	<script type="text/javascript">
		<?php include("javascript/scripts.js"); ?>
	</script>
</html>
