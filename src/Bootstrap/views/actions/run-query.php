<?php

	use Magrathea2\Config;
	use Magrathea2\DB\Database;
	use Magrathea2\MagratheaPHP;

	$config = Config::Instance();
	MagratheaPHP::Instance()->Connect();
	$magDb = Database::Instance();

	$query = $_REQUEST["q"];
	try {
		$rs = $magDb->QueryAll($query);
	} catch(Exception $ex) {
		?>
		<span class="error">ERROR:</span>
		<span><?=$ex->getMsg()?></span>
		<br/>
		<hr/>
		<?php
		print_r($ex);
		return;
	}

	if(!$rs) {
		?>
		<br/>
		<span class="error"> - no answers - </span>
		<?php
	}

	?>
	<div class="col">
		<div class="col-12">
			<span class="query"><?=$query?></span>
		</div>
		<div class="col-12">
			<pre class="query-response"><? print_r($rs); ?></pre>
		</div>
	</div>
