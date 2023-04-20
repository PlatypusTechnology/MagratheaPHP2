<?php

use Magrathea2\Admin\AdminManager;

	$magrathea_action = @$_GET["magrathea_action"];
	if(!empty($magrathea_action)) {
		include("actions/".$magrathea_action.".php");
		die;
	}
?>

<!DOCTYPE html>
<html lang="en">
<?
		$pageTitle = \Magrathea2\Admin\Start::Instance()->title;
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
					}
				?>
			</div>
		</div>
	</body>
	<script type="text/javascript">
		<?php include("javascript/scripts.js"); ?>
	</script>
</html>