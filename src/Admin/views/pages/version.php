<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\MagratheaPHP;

use function Magrathea2\now;

AdminElements::Instance()->Header("Version");

$magPhp = MagratheaPHP::Instance();
$vMagrathea = $magPhp->Version();
$vApp = $magPhp->AppVersion();

$time = now();

echo "<div class='container'>";
echo "<br/>Server time: ".$time;
echo "<br/><br/>App Version: ".$vApp;
echo "<br/>Magrathea Version: ".$vMagrathea;
echo "<br/>Minimum Magrathea Version required: ".@$magPhp->versionRequired;
echo "</div>";
