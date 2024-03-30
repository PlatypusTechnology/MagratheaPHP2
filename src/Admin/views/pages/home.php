<?php
use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;

$adminElements = AdminElements::Instance();
$adminManager = AdminManager::Instance();

$pageTitle = $adminManager->GetTitle();
$adminElements->Header($pageTitle);

?>

<div id="wrapper" class="container setup-wrapper center">
	<?php
		Magrathea2\Admin\AdminManager::Instance()->PrintLogo(400);
	?>
</div>
