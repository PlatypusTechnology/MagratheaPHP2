<?php

use function Magrathea2\now;

$pageTitle = "Server & Info";
\Magrathea2\Admin\AdminElements::Instance()->Header($pageTitle);

?>

<div class="container">
	<div class="card">
		<div class="card-header">
			Server Info
		</div>
		<div class="card-body">
			Now: <?=now()?> / Timezone: <?=date_default_timezone_get()?><br/>
			<a href="https://www.php.net/manual/en/timezones.php" target="_blank">Timezones</a>

		</div>
	</div>

	<div class="card">
		<div class="card-header">
			Timezone check
		</div>
		<div class="card-body">
			<?php
			$levelClass = [
				\Magrathea2\TimezoneCheck::OK => "success",
				\Magrathea2\TimezoneCheck::INFO => "secondary",
				\Magrathea2\TimezoneCheck::WARNING => "warning",
				\Magrathea2\TimezoneCheck::ERROR => "danger",
			];
			foreach(\Magrathea2\TimezoneCheck::Run() as $finding) {
				$message = preg_replace('/`([^`]+)`/', '<code>$1</code>', htmlspecialchars($finding["message"]));
				echo '<div class="alert alert-'.$levelClass[$finding["level"]].' py-1 mb-1">'.$message.'</div>';
			}
			?>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			PHP Info
		</div>
		<div class="card-body">
			<?php phpinfo(); ?>
		</div>
	</div>

</div>
