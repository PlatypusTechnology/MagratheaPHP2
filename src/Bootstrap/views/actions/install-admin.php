<?php

	use Magrathea2\Admin\Install;

	$adminInstall = new Install();

	$installStep = @$_GET["install"];
	if (!$installStep) {
		die("Error calling installation script");
	}

	echo "<br/><hr/><br/>";

	if ($installStep === "db") {
		if ($adminInstall->CheckIfDBInstalled()) {
			die("Database is already installed");
		}
	
		echo "installing database...<br/>";
		$result = $adminInstall->InstallDatabase();
		foreach($result as $row) {
			echo "<span class='title'>running</span><pre>".$row["query"]."</pre>";
			if($row["success"]) {
				echo "<span class='title'>DONE!</span><pre class='rs-success'>";
				print_r($row["result"]);
				echo "</pre>";
			} else {
				echo "<span class='title error'>ERROR</span><pre class='rs-error'>".$row["result"]."</pre>";
			}
			echo "<br/><br/><hr/>";
		}
	}

	if ($installStep === "code") {
		if ($adminInstall->CheckIfAdminFileExists()) {
			die($adminInstall->appPath."/".$adminInstall->adminFileName." file already exists");
		}
		$adminInstall->CreateAdminEntranceFile();
		echo "<br/><br/><hr/>";
		echo "admin file written at [".$adminInstall->getAdminFileEntrance()."]";
	}

