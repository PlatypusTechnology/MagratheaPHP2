<?php
use Magrathea2\Debugger;

error_reporting(E_ALL);
ini_set('display_errors', '1');

$magratheaRoot = realpath(__DIR__."/../../");
$vendorLoad = realpath($magratheaRoot."/vendor/autoload.php")   // standalone checkout
    ?: realpath($magratheaRoot."/../../autoload.php");          // installed as a dependency

require($vendorLoad);

Debugger::Instance()->SetType(Debugger::DEV);
