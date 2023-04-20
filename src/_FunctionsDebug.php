<?php

namespace Magrathea2;


/**
 * Debugs what is sent, according with debug configurations
 * @param 	object 		$bug 		object to debug
 */
function Debug($bug){
	Debugger::Instance()->Add($bug);
}

/**
 * Debugs error, according with debug configurations
 * @param 	object 		$bug 		object to debug
 */
function DebugError($error){
	Debugger::Instance()->AddError($error);
}


/**
 * Adds to info debug, according with configurations
 * @param 	object 		$bug 		object to debug
 */
function Info($bug){
	Debugger::Instance()->Info($bug);
}


?>
