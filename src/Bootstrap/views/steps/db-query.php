<?php

use Magrathea2\MagratheaPHP;

	$database = MagratheaPHP::Instance()->GetDB()->getDatabaseName();

?>

<h5>Execute Query</h5>
<div class="row">
	<?php
	if(!$database) {
		?>
		<div class="col-12">
			Database is not connected by default.<br/>
			To change this behaviour, add the following code to MagratheaPHP start:
			<pre class="code">Magrathea2\MagratheaPHP::Instance()->StartDB()->Load();</pre>
			<br/><hr/>
		</div>
		<?php
	}
	?>
	<div class="col-12">
		Query:
		<textarea class="query-sql" id="query">SHOW VARIABLES;</textarea>
		<button class="btn btn-primary" onclick="runQuery();"> 
			Execute
		</button>
	</div>
	<div class="col-12" id="ajax-response" style="display: none;"></div>
</div>
