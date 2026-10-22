<?php

use Magrathea2\Admin\AdminCsrf;
use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\Start;

// only plain names of files in views/actions/ and views/pages/
$magrathea_subpage = @$_GET["magrathea_subpage"];
if(!empty($magrathea_subpage)) {
	$actionFile = Start::Instance()->GetViewFile("actions", $magrathea_subpage);
	if(!$actionFile) {
		http_response_code(404);
		die("action not found");
	}
	include($actionFile);
	die;
}
$page = @$_GET["magrathea_page"];
$pageFile = $page ? Start::Instance()->GetViewFile("pages", $page) : null;
if($page && !$pageFile) http_response_code(404);
?>

<!DOCTYPE html>
<html lang="en-uk">
	<head>
	<?
		$cssStyleFiles = ["side-menu", "forms", "cards", "tables", "toast", "pre"];
		include("sections/meta.php");
	?>
		<style><?=AdminManager::Instance()->GetCss();?></style>
		<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
		<script type="text/javascript">
			var magrathea_csrf_token = "<?=AdminCsrf::Instance()->GetToken()?>";
			<?php include("javascript/pre-load-scripts.js"); ?>
		</script>
	</head>
	<body>

		<div class="d-flex" id="wrapper">
			<?php include("sections/toast.php"); ?>
			<?php include("sections/menu.php"); ?>
			<?php include("sections/loading.php"); ?>
			<!-- Page content wrapper-->
			<div id="page-content-wrapper">
				<?php
					$gotSomething = false;
					if($page) {
						if($pageFile) {
							include($pageFile);
						} else {
							AdminElements::Instance()->Alert(
								"Page not found [".htmlspecialchars(is_string($page) ? $page : "")."]",
								"danger",
								true
							);
						}
						$gotSomething = true;
					} else {
						$feature = @$_GET["magrathea_feature"];
						if($feature) {
							$f = AdminManager::Instance()->GetFeature($feature);
							if(!$f) {
								AdminElements::Instance()->Alert(
									"Feature not available [".$feature."]",
									"danger",
									true
								);
							} else {
								// Initialize() removed from here because was already initialized in contructor
								// 	change made on 2024-12
								// $f->Initialize();
								$subpage = @$_GET["magrathea_feature_subpage"];
								if(!$subpage) $subpage = "GetPage";
								if($subpage !== "GetPage" && !$f->HasPermission($subpage)) {
									AdminManager::Instance()->PermissionDenied();
								}
								$f->$subpage();
								$gotSomething = true;
							}
						}
					}
					if(!$gotSomething) {
						include("pages/home.php");
					}
				?>
			</div>
		</div>
	</body>
	<script type="text/javascript"><?=AdminManager::Instance()->GetJs();?></script>
</html>