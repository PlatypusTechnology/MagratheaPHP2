<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\MagratheaPHP;

$adminElements = AdminElements::Instance();
$adminElements->Header("File Editor");

$base = MagratheaPHP::Instance()->appRoot;
$file = @$_GET["file"];

?>

<style>
<?php
	include("style.css");
?>
</style>

<div class="container">

	<div class="card">
		<div class="card-header">
			Open file
		</div>
		<div class="card-body">
			<div class="inline">
				<pre class="inline"><?=$base?></pre> /
			</div>
			<div class="inline">
			<?php
			$adminElements->Input("text", "file", false, $file, ["inline"]);
			?>
			</div>
			<div class="inline right">
			<?php
			$adminElements->Button("View file", "openFile()", ["btn-primary", "m-0"]);
			?>
			</div>
		</div>
	</div>

	<div class="row mt-2">
		<div class="col-12" id="file-editor-view">
		</div>
	</div>

</div>

<script type="text/javascript">
<?php
	include("scripts.js");
?>
</script>
