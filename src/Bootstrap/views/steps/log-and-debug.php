<?php
	use Magrathea2\Config;

	$config = Config::Instance();
	$env = $config->GetEnvironment();

	$logPath = $config->Get("logs_path");

	$logPathOk = realpath($logPath);
?>

<h3>Log</h3>
<div class="row">
	<?php
		if (!$logPathOk) {
			?>
			<div class="col-12">
				<span class="error text-lg">ERROR:</span>
				<span class="error">Log Folder does not exist!</span><br/>
				<?=$logPath?>
			</div>
			<?php
		} else {
			?>
			<div class="col-12">
				Log Path: <pre><?=$logPath?></pre>
			</div>
			<?php
		}
	?>
</div>
<hr/>
<h3>Debug</h3>
<div class="row">
	<div class="col-12">
		Debugging:
<pre class="code">

use Magrathea2\Debugger;

Debugger::Instance()->SetType(Debugger::DEV);
		// Will print the debugs as it appears in the code

Debugger::Instance()->SetType(Debugger::DEBUG);
		// Will store all the debugs and print it later

Debugger::Instance()->SetType(Debugger::LOG);
		// Will log queries and other debugs in the code

Debugger::Instance()->SetType(Debugger::NONE);
		// Well... nothing to do, heh?

</pre>
		default behaviour: LOG
	</div>
</div>
