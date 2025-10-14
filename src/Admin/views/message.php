<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;

$admin = AdminManager::Instance();
$elements = AdminElements::Instance();

?>

<!DOCTYPE html>
<html lang="en">
	<head>
	<?
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
				<div class="card">
					<div class="card-header center card-header-login">
						<div class="card-logo">
							<?
								Magrathea2\Admin\AdminManager::Instance()->PrintLogo(200);
							?>
						</div>
						<div class="card-title"><span>Login</span></div>
					</div>
					<div class="card-body center message">
						<?=$message?>
					</div>
				</div>

			</div>

			<br/><br/>
				<div class="card">
					<div class="card-body">
						<div class="row">
							<div class="col-4 offset-8">
								<?
								$logoutLink = \Magrathea2\Admin\AdminUrls::Instance()->GetLogoutUrl();
								$elements->Button("Logout", "window.location.href='".$logoutLink."'", ["btn-danger", "w-100"]);
								?>
							</div>
						</div>
					</div>
				</div>
		</div>
	</body>
	<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
	<script type="text/javascript">
		<?php include("javascript/scripts.js"); ?>
	</script>
</html>
