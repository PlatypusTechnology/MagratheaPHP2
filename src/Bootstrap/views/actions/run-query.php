<?php

	use Magrathea2\Config;
	use Magrathea2\DB\Database;
	use Magrathea2\MagratheaPHP;

	$config = Config::Instance();
	MagratheaPHP::Instance()->Connect();
	$magDb = Database::Instance();

	// the query, its results and the error come from the database: escaped
	$query = $_REQUEST["q"];
	try {
		$rs = $magDb->QueryAll($query);
	} catch(Exception $ex) {
		?>
		<span class="error">ERROR:</span>
		<span><?=htmlspecialchars($ex->getMessage())?></span>
		<br/>
		<hr/>
		<pre><?=htmlspecialchars(print_r($ex, true))?></pre>
		<?php
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
			<span class="query"><?=htmlspecialchars($query)?></span>
		</div>
		<div class="col-12">
			<pre class="query-response"><?=htmlspecialchars(print_r($rs, true))?></pre>
		</div>
	</div>
