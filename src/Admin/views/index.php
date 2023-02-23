<?php
	$magrathea_action = @$_GET["magrathea_action"];
	if(!empty($magrathea_action)) {
		include("actions/".$magrathea_action.".php");
		die;
	}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Magrathea Admin</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
		<style>
			<?php include("css/_variables.css"); ?>
			<?php include("css/styles.css"); ?>
		</style>
	</head>
	<body>

		<div class="d-flex" id="wrapper">
			<?php include("sections/menu.php"); ?>
			<!-- Page content wrapper-->
			<div id="page-content-wrapper">
				<?php
					$page = @$_GET["page"];
					if($page) {
						include("pages/".$page.".php");
					}
				?>
			</div>
		</div>
	</body>
	<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
	<script type="text/javascript">
		<?php include("javascript/scripts.js"); ?>
	</script>
</html>