<?php

namespace Magrathea2;

use Magrathea2\Errors\ErrorManager;
use Magrathea2\Exceptions\MagratheaDBException;
use Magrathea2\Exceptions\MagratheaException;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####
#######################################################################################
####
####    Class for debugging and logging Control
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################


/**
 * Magrathea Debugger can manage anything for debugging and error-searching through Magrathea Codes.
 * It can trace errors, save log files, print queries and do a bunch of functions that would help the developer on error searching
 */
class Debugger extends Singleton {

	const NONE = 0;
	const LOG = 2;
	const DEBUG = 3;
	const DEV = 4;
	const ANALYSIS = 5;

	private $logFile = null;
	private $debugItems = array();
	private $debugType = self::LOG;
	private $queries = false;
	private $oldDebugType = self::NONE;
	private $captureDepth = 0;
	private $captured = array();

	/**
	* Sets the debugger method
	* It can be: *DEV*, *DEBUG*, *LOG*, *NONE* =>
	* **Debugger::DEV** = Will print the debugs as it appears in the code (in an API call, they go to the response's `_debug`)
	* **Debugger::DEBUG** = Will store all the debugs and print it later
	* **Debugger::LOG** = Will log queries and other debugs in the code
	* **Debugger::ANALYSIS** = Will return the error with the API
	* **Debugger::NONE** = Well... nothing to do, heh?
	* Default: **LOG**
	*
	* @param 	$type 		integer 	type to be set
	* @return  	Debugger
	*/
	public function SetType($type){
		// echo "setting type: ".$type; var_dump(debug_backtrace());
		$this->debugType = $type;
		return $this;
	}

	/**
	 * Sets debug mode as dev
	 * @return Debugger
	 */
	public function SetDev() {
		$this->SetType(Debugger::DEV);
		return $this;
	}

	/**
	 * Sets debug mode as debug
	 * @return Debugger
	 */
	public function SetDebug() {
		$this->SetType(Debugger::DEBUG);
		return $this;
	}

	/**
	 * Should debugger log queries?
	 * @param 	boolean 	$q 		true for logging queries, false for not
	 * @return  Debugger
	 */
	public function LogQueries($q){
		$this->queries =  $q;
		return $this;
	}

	/**
	 * Set the name of log file that will be used
	 * 	(it will be created inside "*logs*" folder)
	 * @param 	string 		$file 		log file name
	 * @return  Debugger
	 */
	public function SetLogFile($file){
		$this->logFile = $file;
		return $this;
	}

	/**
	 * Trace error location
	 */
	public function Trace(){
		echo "<pre>"; debug_backtrace(); echo "</pre>";
	}

	/**
	* Sets a debugger type temporarily
	* (usefull when need to check a specific operation, for example)
	*
	* @param 	string 	$dType 	type to be temporarily set
	* @return  	Debugger
	*/
	public function SetTemp($dType){
		$this->oldDebugType = $this->debugType;
		$this->debugType = $dType;
		return $this;
	}

	/**
	* After temporarily setting a debugging type, gets it back to what it was
	*
	* @return  	Debugger
	*/
	public function BackTemp(){
		$this->debugType = $this->oldDebugType;
		return $this;
	}

	/**
	* Returns the debug type
	* @return string Debug type (*Dev*, *Debug*, *Log*, *None*)
	*/
	public function GetType(){
		return $this->debugType;
	}
	/**
	* Returns the debug type in a descritive format
	* @return string Debug type (*Dev*, *Debug*, *Log*, *None*)
	*/
	public function GetTypeDesc(): string {
		switch($this->debugType) {
			case self::NONE: 			return "NONE";
			case self::LOG: 			return "LOG";
			case self::DEBUG:			return "DEBUG";
			case self::DEV:				return "DEV";
			case self::ANALYSIS:	return "ANALYSIS";
			default: 					return "unknown";
		}
	}

	/**
	* Add an item to the debug array object
	* 	@param 	string 	$debug 		debug item
	*/
	public function Add($debug){
		switch ($this->debugType) {
			case self::NONE:
				return;
			case self::LOG:
				if($debug instanceof \Magrathea2\Exceptions\MagratheaConfigException) {
					if($debug->killerError) {
						ErrorManager::Instance()->DisplayException($debug);
					}
				} else if($debug instanceof \Exception) {
					Logger::Instance()->LogError($debug);
				} else {
					Logger::Instance()->Log($debug);
				}
				return;
			case self::DEV:
				if($this->IsCapturing()) $this->Capture($debug);
				else echo "<pre>".$debug."</pre>";
				array_push($this->debugItems, $debug);
				break;
			case self::DEBUG:
				// stored items are printed at the end of the request, after the JSON: an API call collects them instead
				if($this->IsCapturing()) $this->Capture($debug);
				else array_push($this->debugItems, $debug);
				break;
		}
	}

	/**
	 * Add an info item to the debug array object
	 * 	@param 		string 		$debug 		info item
	 */
	public function Info($debug){
		switch ($this->debugType) {
			case self::NONE:
			case self::LOG:
				Logger::Instance()->Log(var_dump($debug));
				return;
			case self::DEV:
				if($this->IsCapturing()) $this->Capture("INFO: ".$debug);
				else echo "<pre>INFO: ".$debug."</pre>";
				array_push($this->debugItems, "<pre>".$debug."</pre>");
				break;
			case self::DEBUG:
				if($this->IsCapturing()) $this->Capture("INFO: ".$debug);
				else array_push($this->debugItems, "<pre>".$debug."</pre>");
				break;
		}
	}

	/**
	 * Adds an error to the debugger
	 * @param \Exception		$err 	Exception
	 */
	public function AddError($err){
		$this->Add($err);
	}

	/** 
	* Adds a query to the debug
	*
	*	@param 	string 	$sql 	query to be debugged
	* @param 	string 	$values 	values to be added to the query
	*/
	public function AddQuery($sql, $values){
		if($this->debugType == self::NONE) return;
		if(!$this->queries) return;
		$logThis = "query run: [".$sql."]";
		if(!is_null($values)){
			$values = print_r($values, true);
			$logThis .= " - values: [".$values."]";
		}
		$this->Add($logThis);
	}

	/**
	 * Adds an error to the debugger
	 * @param \Exception		$err 	Exception
	 */
	public function Error($err){
		$this->Add($err->getMessage());
	}

	/**
	 * Starts collecting debug items for an API response (`MagratheaApi::ExecuteUrl()` calls it).
	 * Until the matching `StopCapture()`, DEV and DEBUG collect items instead of printing them
	 * (DEV: right away, DEBUG: at the end of the request), so they don't break the JSON;
	 * `WithCaptured()` adds them to the response.
	 * Calls nest: only the outermost one starts and ends the collection.
	 * @return Debugger
	 */
	public function StartCapture(): Debugger {
		if($this->captureDepth == 0) $this->captured = array();
		$this->captureDepth++;
		return $this;
	}

	/**
	 * Ends a `StartCapture()`. The outermost one drops the collected items.
	 * @return Debugger
	 */
	public function StopCapture(): Debugger {
		if($this->captureDepth > 0) $this->captureDepth--;
		if($this->captureDepth == 0) $this->captured = array();
		return $this;
	}

	/**
	 * Is the debugger collecting items for an API response?
	 * @return bool
	 */
	public function IsCapturing(): bool {
		return $this->captureDepth > 0;
	}

	/**
	 * Items collected since `StartCapture()`, ready to be JSON-encoded:
	 * exceptions as `{type, message, code, file, data?, query?, values?}`, anything else as it was added.
	 * @return array<mixed>
	 */
	public function GetCaptured(): array {
		return array_map(fn($item) => $this->CapturedItem($item), $this->captured);
	}

	/**
	 * Adds the collected items to an API response, as `_debug`.
	 * In DEBUG, the items stored before the call come first: `ShowAtShutdown()` doesn't print after a JSON response.
	 * Only an associative array gets it: a list or a scalar would change shape.
	 * Nothing collected, no `_debug`.
	 * @param 	mixed 	$response 	response to be JSON-encoded
	 * @return 	mixed 	the response, with `_debug` if it applies
	 */
	public function WithCaptured($response) {
		$items = $this->captured;
		if($this->debugType == self::DEBUG) $items = array_merge($this->debugItems, $items);
		if(empty($items) || !is_array($response) || empty($response)) return $response;
		if(array_keys($response) === range(0, count($response) - 1)) return $response;
		$debug = array_map(fn($item) => $this->CapturedItem($item), $items);
		if(json_encode($debug) === false) {
			// a value that can't be encoded (invalid UTF-8, recursion) must not take the response down with it
			$debug = ["_debug could not be JSON-encoded: ".json_last_error_msg()];
		}
		$response["_debug"] = $debug;
		return $response;
	}

	private function Capture($debug) {
		// the same exception can be added twice: it adds itself, then gets caught and passed to AddError()
		if(is_object($debug) && in_array($debug, $this->captured, true)) return;
		array_push($this->captured, $debug);
	}

	/**
	 * Formats a collected item when it's read, not when it's added:
	 * MagratheaException adds itself before its code and data are set.
	 */
	private function CapturedItem($item) {
		if(!($item instanceof \Throwable)) return $item;
		$rs = [
			"type" => get_class($item),
			"message" => $item->getMessage(),
			"code" => $item->getCode(),
			"file" => $item->getFile().":".$item->getLine(),
		];
		if($item instanceof MagratheaDBException) {
			$rs["query"] = $item->query;
			if($item->values !== null) $rs["values"] = $item->values;
		}
		if($item instanceof MagratheaException && $item->GetData() !== null) {
			$rs["data"] = $item->GetData();
		}
		return $rs;
	}

	/**
	 * In DEBUG, prints the stored items at the end of the request (a shutdown function calls it).
	 * Not after a JSON response: the HTML would break it (an API response has them in `_debug`).
	 * @param 	array|null 	$headers 	response headers (default: `headers_list()`)
	 */
	public function ShowAtShutdown(?array $headers = null) {
		if($this->debugType != self::DEBUG) return;
		$headers = $headers ?? headers_list();
		if(!empty(preg_grep('/^content-type:\s*application\/json/i', $headers))) return;
		$this->Show();
	}

	/**
	* Prints the debug
	*/
	public function Show(){
		if(empty($this->debugItems)) return;
		echo "<div style='width: 95%; border: 2px solid red; background-color: yellow; padding: 10px; margin: 10px;'>";
		echo "MAGRATHEA DEBUG<br/><br/>";
		foreach ($this->debugItems as $item) {
			echo $this->printsDebug($item);
		}
		echo "</div>";
	}

	/**
	 * prints every single line in p_r()
	 * @param  	object 		$debugToPrint 		object to debug
	 */	
	private function printsDebug($deb){
		$html = "";
		if(is_a($deb, "Magrathea2\Exceptions\MagratheaException")){
			$html .= "<div style='padding: 10px; border: 1px dotted black; margin-bottom: 5px; line-height: 120%;'>";
			$html .= "<span style='color: red; font-weight: bold;'>".get_class($deb)."</span><br/><br/>";
			$html .= "<span><pre style='width: 100%; overflow-x: scroll;'>".$deb."\n\n</pre></span><br/>";
			$html .= "<span style='padding-left: 20px;'><b>at: </b> ==> ";
			$html .= "<b>File: </b>".$deb->getFile()." / <b>line: </b>[".$deb->getLine()."]</span><br/>";
			$html .= "<div style='padding-left: 20px;'><b>trace: </b> ==> <br/>";
			$html .= "<div style='padding-left: 10px;'>".$this->printTrace($deb->getTrace())."</div></div>";
			$html .= "</div>";
		} else if (is_array($deb)) {
			$html .= "<div style='padding: 10px; border: 1px dotted black; margin-bottom: 5px; line-height: 120%;'>";
			$html .= "<span>".\Magrathea2\p_r($deb)."</span><br/>";
			$html .= "</div>";
		} else {
			$html .= "<div style='padding: 10px; border: 1px dotted black; margin-bottom: 5px; line-height: 120%;'>";
			$html .= "<pre>".\Magrathea2\p_r($deb)."</pre><br/>";
			$html .= "</div>";
		}
		return $html;
	}

	/**
	 * gets a trace array and returns a beautiful way of it printed
	 * @param  	array 		$trace 		trace
	 * @return 	string        			html of the trace printed
	 */
	private function printTrace($trace){
		$html = "";
		foreach ($trace as $key => $val) {
			$html .= "[".$key."]<br/>";
			$html .= "&nbsp;&nbsp;&nbsp;&nbsp;<b>[file]</b> = ".@$val["file"]." <b>[line]</b> = ".@$val["line"]."<br/>";
			$html .= "&nbsp;&nbsp;&nbsp;&nbsp;<b>[class]</b> = ".@$val["class"]." <b>[function]</b> = ".@$val["function"]."<br/>";
		}
		return $html;
	}


}

?>