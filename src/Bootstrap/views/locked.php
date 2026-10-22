<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Magrathea Bootstrap</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
		<style>
			<?php include("css/_variables.css"); ?>
			<?php include("css/styles.css"); ?>
		</style>
	</head>
	<body>
		<main>
			<div class="container mt-4">
				<div class="card">
					<h3>Bootstrap is locked</h3>
					<?php if(!empty($environment)) { ?>
						<p>Environment: <b><?=htmlspecialchars($environment)?></b></p>
					<?php } ?>
				</div>
			</div>
		</main>
	</body>
</html>
