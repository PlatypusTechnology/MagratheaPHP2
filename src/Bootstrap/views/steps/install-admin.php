<?php

use Magrathea2\DB\Database;
use Magrathea2\MagratheaPHP;

	MagratheaPHP::Instance()->Connect();
	$adminInstall = new Magrathea2\Admin\Install();
	$db = Database::Instance()->getDatabaseName();
	$folder = MagratheaPHP::Instance()->appRoot;
	$adminCode = $adminInstall->GetAdminCode();

?>

<?php
	if(!$db) {
		?>
	<div class="row">
		<div class="col-12">
			<div class="error">
				Database is Empty. For installing Magrathea Admin, it's necessary to start db!
			</div>
		</div>
	</div>
		<?php
	}
?>

<div class="row mb-2">
	<div class="col-8">
		Installing Database in <span class="pre"><?=$db?></span> database
	</div>
	<div class="col-2">
		<button class="btn btn-primary" onclick="installAdminPreview('db');">View SQL</button>
	</div>
	<div class="col-2">
		<button class="btn btn-success" onclick="installAdmin('db');">Install</button>
	</div>
</div>

<div class="row mb-2">
	<div class="col-8">
		Create <span class="pre">admin.php</span> file
		<br/>on folder <span class="pre"><?=$folder?></span>.
	</div>
	<div class="col-2">
		<button class="btn btn-primary" onclick="installAdminPreview('code');">View code</button>
	</div>
	<div class="col-2">
		<button class="btn btn-success" onclick="installAdmin('code');">Install</button>
	</div>
</div>

<div class="row mb-2">
	<div class="col-4"></div>
	<div class="col-4">
		<button class="btn btn-primary btn-100" onclick="window.location.href='/admin.php'">GO TO ADMIN</button>
	</div>
	<div class="col-4"></div>
</div>

<div class="row mb-2">
	<div class="col-12 preview-admin-container" style="display: none;">
		<div class="close-bt" onclick="hidePreview();">x</div>
		<pre class="code" id="preview-admin"></pre>
	</div>
	<div class="col-12">
		<div id="install-rs" style="display: none;"></div>
	</div>
</div>

<script type="text/javascript">
	function installAdmin(step) {
		let url = getBootstrapUrl()+"?action=install-admin&install="+step;
		ajax("GET", url).then((rs) => {
			showOn("#install-rs", rs);
		});
	}

	function installAdminPreview(step) {
		let url = getBootstrapUrl()+"?action=install-admin-preview&view="+step;
		ajax("GET", url).then((rs) => {
			showOn("#preview-admin", rs);
			$(".preview-admin-container").show("slow");
		});
	}

	function hidePreview() {
		$(".preview-admin-container").hide("slow");
	}
</script>
