<?php

use Magrathea2\Admin\AdminUrls;

https://startbootstrap.github.io/startbootstrap-simple-sidebar/

/** @var \Magrathea2\Admin\AdminManager $manager */
$manager = Magrathea2\Admin\AdminManager::Instance();
$env = Magrathea2\Config::Instance()->GetEnvironment();

function getMainTitle($item) {
	return '<span class="title main">'.$item['title'].'</span>';
}

function getTitle($item) {
	return '<span class="title">'.$item['title'].'</span>';
}

function getExternalLink($item) {
	$link = $item['link'];
	$title = $item['title'];
	return '<a class="'.@$item['class'].'" href="'.$link.'" target="_blank">'.$title.' &nearhk;</a>';
}

function getEmpty($item) {
	$class = @$item['class'];
	if(empty($class)) $class = 'mt-4';
	return '<div class="'.$class.'"></div>';
}

function getItem($item) {
	if(empty(@$item["link"])) {
		if (!empty(@$item["feature"])) {
			$link = AdminUrls::Instance()->GetFeatureUrl($item["feature"]);
		} else {
			$link = "#";
		}
	} else {
		$link = $item['link'];
	}
	return '<a class="'.@$item['class'].'" href="'.$link.'">'.$item['title'].'</a>';
}

?>

<div class="border-end bg-white side-menu">
	<div class="sidebar-heading border-bottom bg-light">
		<?$manager->PrintLogo(50)?>
		<h1><?=$manager->GetTitle()?></h1>
		<div class="env-container">
			<span class="env-title"><?=$env?></span>
		</div>
	</div>
	<div class="list-group list-group-flush">
		<ul class="p-0">
		<?php
			/** @var  \Magrathea2\Admin\AdminManager::GetMenu */
			$menuItems = $manager->GetMenu();
			foreach($menuItems->GetMenu() as $item) {
				echo '<li class="list-group-item list-group-item-action list-group-item-light '.(@$item['active'] ? 'active' : '').'">';
				$type = @strtolower($item['type']);
				switch($type) {
					case "main":
						echo getMainTitle($item);
						break;
					case "sub":
						echo getTitle($item);
						break;
					case "empty":
						echo getEmpty($item);
						break;
					case "link":
						echo getExternalLink($item);
						break;
					default:
						echo getItem($item);
						break;
				}
				echo '</li>';
			}
		?>
		</ul>
	</div>
</div>