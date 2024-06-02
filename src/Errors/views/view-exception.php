<?php
	$bootstrapFolder = __DIR__."/../../Bootstrap/views/";
?>
<!DOCTYPE html>
<html lang="en-uk">

	<head>
		<meta charset="utf-8">
		<title>Magrathea Error!</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
		<style>
			<?php include($bootstrapFolder."css/_variables.css"); ?>
			<?php include($bootstrapFolder."css/styles.css"); ?>
			<?php include($bootstrapFolder."css/steps.css"); ?>
		</style>
	</head>

	<body>
		<main class="container">
			<?php
			$headerTitle = "Magrathea Fatal Error!";
			include($bootstrapFolder."/sections/header.php");
			?>

			<div class="mt-4">
				<div class="row">
					<div class="col-sm-12">
						There was a fatal error:
					</div>
					<div class="col-sm-12">
						<?=$errorMessage?>
					</div>
					<? if(@$ex) { ?>
					<div class="col-sm-12">
						<pre class="log-response" style="max-height: 500px;"><?php print_r($ex); ?></pre>
					</div>
					<? } ?>
				</div>
			</div>
			<hr/>
			<?php
			if(!empty($extraMessagePage)) {
				include(__DIR__."/".$extraMessagePage);
			}
			?>

		</main>
	</body>

</html>