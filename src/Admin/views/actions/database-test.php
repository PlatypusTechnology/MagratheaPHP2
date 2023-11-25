<?php

	use Magrathea2\Config;
	use Magrathea2\DB\Database;
	use Magrathea2\MagratheaPHP;

	$config = Config::Instance();
	MagratheaPHP::Instance()->Connect();
	$magDb = Database::Instance();

	$success = false;
	try {
		$success = $magDb->OpenConnectionPlease();
		$magDb->CloseConnectionThanks();
	} catch(Exception $ex){
		$success = $ex->getMessage();
	}

	if($success) {
		?>
		<span class="success">Connection successful!</span>
		<?
	}
