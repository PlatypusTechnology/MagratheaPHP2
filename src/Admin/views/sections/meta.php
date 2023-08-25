<?php
use Magrathea2\Admin\AdminManager;

$adminManager = AdminManager::Instance();
?>

<head>
	<meta charset="utf-8">
	<title><?=$pageTitle?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
	<style>
		:root {
			--white: #FFF;
			--black: #000;
			--gray: #CCC;
			--error: #C32;
			--success: green;

			--primary: rgb(<?=$adminManager->GetColor()?>);
			--primary-light: rgba(<?=$adminManager->GetColor()?>, .5);
		}
		<?php 
			include(__DIR__."/../css/_variables.css");
			include(__DIR__."/../css/styles.css");
			if( isset($cssStyleFiles) ) {
				foreach ($cssStyleFiles as $file) {
					include(__DIR__."/../css/".$file.".css");
				}
			}
		?>
	</style>
</head>
