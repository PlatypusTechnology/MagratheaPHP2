<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Config;
use Magrathea2\MagratheaPHP;

use function Magrathea2\now;

AdminElements::Instance()->Header("Version");

$magPhp = MagratheaPHP::Instance();

?>
<div class="container">
	<div class="card">
		<div class="card-header">
			Server Version Information
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<b>App Version: <?=$magPhp->AppVersion();?></b> <br/><br/>

			Magrathea PHP Version: <?=$magPhp->version()?> <br/>
			Minimum Magrathea PHP Version Required: <?=@$magPhp->versionRequired?> <br/><br/>
			PHP Version: <?=phpversion()?> <br/>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			Server Time
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<b><?=now()?></b><br/><br/>
			Current Timezone = <?=date_default_timezone_get()?><br/>
			ConfigApp("<i>timezone</i>") = <?=Config::Instance()->Get("timezone")?><br/>
		</div>
	</div>

</div>
