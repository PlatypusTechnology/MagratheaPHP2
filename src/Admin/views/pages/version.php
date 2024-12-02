<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\MagratheaPHP;

AdminElements::Instance()->Header("Version");

$magPhp = MagratheaPHP::Instance();
$vMagrathea = $magPhp->Version();
$vApp = $magPhp->AppVersion();

echo "<div class='container'>";
echo "<br/>App Version: ".$vApp;
echo "<br/>Magrathea Version: ".$vMagrathea;
echo "<br/>Minimum Magrathea Version required: ".@$magPhp->versionRequired;
echo "</div>";
