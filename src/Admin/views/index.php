<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;

use function Magrathea2\p_r;

	$magrathea_subpage = @$_GET["magrathea_subpage"];
	if(!empty($magrathea_subpage)) {
		include("actions/".$magrathea_subpage.".php");
		die;
	}
?>

<!DOCTYPE html>
<html lang="en">
<?
		$pageTitle = AdminManager::Instance()->title;
		$cssStyleFiles = ["side-menu", "forms", "cards", "tables", "toast"];
		include("sections/meta.php");
	?>
		<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
		<script type="text/javascript">
			<?php include("javascript/pre-load-scripts.js"); ?>
		</script>
	<body>

		<div class="d-flex" id="wrapper">
			<?php include("sections/toast.php"); ?>
			<?php include("sections/menu.php"); ?>
			<?php include("sections/loading.php"); ?>
			<!-- Page content wrapper-->
			<div id="page-content-wrapper">
				<?php
					$page = @$_GET["magrathea_page"];
					if($page) {
						include("pages/".$page.".php");
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
								$f->GetPage();
							}
						}
					}
				?>
			</div>
		</div>
	</body>
	<script type="text/javascript">
		<?php include("javascript/scripts.js"); ?>
	</script>
</html>