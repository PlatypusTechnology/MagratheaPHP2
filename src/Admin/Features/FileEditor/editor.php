<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\MagratheaPHP;

$base = MagratheaPHP::Instance()->appRoot;

if(isset($viewFile)) {
	$file = $viewFile;
} else {
	$file = @$_POST["file"];
}
if(empty($file)) {
	\Magrathea2\Admin\AdminElements::Instance()->Alert("file can't be empty", "danger");
	die;
}

$file = $base."/".$file;

if(!file_exists($file)) {
	$msg = "file [".$file."] does not exist";
	\Magrathea2\Admin\AdminElements::Instance()->Alert($msg, "danger");
	die;
}

?>

<div class="card">
	<div class="card-header">
		<?=$file?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<pre class="code file-pre">
<?
$lines = @$_POST["lines"];
if(empty($lines)) {
	echo htmlentities(file_get_contents($file));
	die;
}
?>
		</pre>
	</div>
</div>
