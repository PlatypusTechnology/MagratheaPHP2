<?php

use Magrathea2\Config;

$fileLocation = Config::Instance()->GetFilePath();
?>

<div class="row col-mt4">
	<div class="col-md-12">
		Magrathea Configuration does not exists!<br/>
		We were looking for it at <pre class="inline code"><?=$fileLocation?></pre>
	</div>
	<div class="col-md-12 mt-2">
		Magrathea conf sample:
		<pre class="code"><?php
	$bootstrapRoot = __DIR__."/../../Bootstrap/";
	include($bootstrapRoot."/docs/magrathea.conf.sample");
		?></pre>
	</div>
</div>

