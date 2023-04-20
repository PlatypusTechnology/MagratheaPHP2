<?php
use Magrathea2\Debugger;

$magratheaRoot = realpath(__DIR__."/../../");
$vendorLoad = realpath($magratheaRoot."/../../autoload.php");

require_once($vendorLoad);

Debugger::Instance()->SetType(Debugger::NONE);

