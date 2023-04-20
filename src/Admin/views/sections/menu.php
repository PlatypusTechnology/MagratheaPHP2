<?php
https://startbootstrap.github.io/startbootstrap-simple-sidebar/

$admin = Magrathea2\Admin\Start::Instance();
$manager = Magrathea2\Admin\AdminManager::Instance();

$env = Magrathea2\Config::Instance()->GetEnvironment();

?>


<div class="border-end bg-white side-menu">
	<div class="sidebar-heading border-bottom bg-light">
		<?$manager->PrintLogo(50)?>
		<h1><?=$admin->title?></h1>
		<div class="env-container">
			<span class="env-title"><?=$env?></span>
		</div>
	</div>
	<div class="list-group list-group-flush">
		<ul class="p-0">
		<?php
			$menuItems = $manager->GetMenuItems();
			foreach($menuItems as $item) {
				echo '<li class="list-group-item list-group-item-action list-group-item-light '.(@$item['active'] ? 'active' : '').'">';
				if(@$item['link']) {
					echo '<a class="'.@$item['class'].'" href="'.$item['link'].'">'.$item['title'].'</a>';
				} else {
					echo '<span class="title">'.$item['title'].'</span>';
				}
				echo '</li>';
			}
		?>
		</ul>
	</div>
</div>