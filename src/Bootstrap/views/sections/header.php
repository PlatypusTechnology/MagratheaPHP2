<?php
$headerTitle = $headerTitle ?? "Magrathea Bootstrap";
?>

<div class="container">
	<header class="d-flex flex-wrap justify-content-center py-3 mb-4 border-bottom">
		<a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none">
			<div class="logo">
				<? include(__DIR__."/../logo.svg"); ?>
			</div>
			<span class="fs-4"><?=$headerTitle?></span>
		</a>
	</header>
</div>