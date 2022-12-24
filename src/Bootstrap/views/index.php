<?php
	global $stepActive;
	$action = @$_GET["action"];
	if(!empty($action)) {
		include("actions/".$action.".php");
		die;
	}
	$stepActive = @$_GET["step"];
	if(empty($stepActive)) $stepActive = 1;
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Magrathea Bootstrap</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
		<style>
			<?php include("css/_variables.css"); ?>
			<?php include("css/styles.css"); ?>
			<?php include("css/steps.css"); ?>
		</style>
	</head>
	<body>
		<main>
			<?php include("sections/header.php"); ?>
			<div class="container">
				<div class="row">
					<div class="col-4">
					<?php include("sections/steps.php"); ?>
					</div>
					<div class="col-8">
						<div class="card">
							<?php
							switch($stepActive) {
								default:
								case 1:
									include("steps/structure.php");
									break;
								case 2:
									include("steps/config-file.php");
									include("steps/config-edit.php");
									break;
								case 3:
									include("steps/log-and-debug.php");
									break;
								case 4:
									include("steps/db-connect.php");
									break;
								}
							?>
						</div>
						<div class="actions">
							<?php if ($stepActive > 1) { ?>
								<button class="btn btn-primary left" onclick="window.location.href='/?step=<?=$stepActive-1?>'">Back</button>
							<?php } ?>
							<button class="btn btn-success" onclick="window.location.href='/?step=<?=$stepActive+1?>'">Next</button>
						</div>
					</div>
				</div>

			</div>
		</main>
	</body>
	<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
	<script type="text/javascript">
		<?php include("javascript/config.js"); ?>
		<?php include("javascript/database.js"); ?>
	</script>
</html>