<?php

namespace Magrathea;

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
 * Debugs what is sent, according with debug configurations
 * @param 	object 		$bug 		object to debug
 */
function Debug($bug){
	MagratheaDebugger::Instance()->Add($bug);
}

/**
 * Debugs error, according with debug configurations
 * @param 	object 		$bug 		object to debug
 */
function DebugError($error){
	MagratheaDebugger::Instance()->AddError($error);
}


/**
 * Adds to info debug, according with configurations
 * @param 	object 		$bug 		object to debug
 */
function Info($bug){
	MagratheaDebugger::Instance()->Info($bug);
}

/**
 * Magrathea Debugger can manage anything for debugging and error-searching through Magrathea Codes.
 * It can trace errors, save log files, print queries and do a bunch of functions that would help the developer on error searching
 */
class MagratheaDebugger {

	const NONE = 0;
	const LOG = 2;
	const DEBUG = 3;
	const DEV = 4;

	private $logFile = null;
	private $debugItems = array();
	private $debugType = self::LOG;
	private $queries = false;
	private $oldDebugType = self::NONE;
	protected static $inst = null;

	// this is a singleton!
	private function __construct(){ }

	/**
	* Static singleton way to get the debugger instance
	*/
	public static function Instance(){
		if (self::$inst === null) {
			self::$inst = new MagratheaDebugger();
		}
		return self::$inst;
	}
	public function MagratheaDebugger(){ }

	/**
	* Sets the debugger method
	* It can be: *DEV*, *DEBUG*, *LOG*, *NONE* =>
	* **MagratheaDebugger::DEV** = Will print the debugs as it appears in the code
	* **MagratheaDebugger::DEBUG** = Will store all the debugs and print it later
	* **MagratheaDebugger::LOG** = Will log queries and other debugs in the code
	* **MagratheaDebugger::NONE** = Well... nothing to do, heh?
	* Default: **LOG**
	*
	* @param 	$dType 	string 	type to be set
	* @return  	itself
	*/
	public function SetType($dType){
		$this->debugType = $dType;
		return $this;
	}

	/**
	 * Should debugger log queries?
	 * @param 	boolean 	$q 		true for logging queries, false for not
	 * @return  itself
	 */
	public function LogQueries($q){
		$this->queries =  $q;
		return $this;
	}

	/**
	 * Set the name of log file that will be used
	 * 	(it will be created inside "*logs*" folder)
	 * @param 	string 		$file 		log file name
	 * @return  itself
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
	* @return  	itself
	*/
	public function SetTemp($dType){
		$this->oldDebugType = $this->debugType;
		$this->debugType = $dType;
		return $this;
	}

	/**
	* After temporarily setting a debugging type, gets it back to what it was
	*
	* @return  	itself
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
	* Add an item to the debug array object
	* 	@param 	string 	$debug 		debug item
	*/
	public function Add($debug){
		switch ($this->debugType) {
			case self::NONE:
				return;
				break;
			case self::LOG:
				if($debug instanceof Exception) {
					MagratheaLogger::LogError(dump($debug), $this->logFile);
				} else {
					MagratheaLogger::Log(dump($debug), $this->logFile);
				}
				return;
				break;
			case self::DEV:
				echo "<pre>".$debug."</pre>";
			case self::DEBUG:
				array_push($this->debugItems, $debug);
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
				MagratheaLogger::Log(dump($debug), $this->logFile);
				return;
				break;
			case self::DEV:
				echo "<pre>INFO: ".$debug."</pre>";
			case self::DEBUG:
				array_push($this->debugItems, "<pre>".$debug."</pre>");
				break;
		}
	}

	/**
	 * Adds an error to the debugger
	 * @param Exception		$err 	Exception
	 */
	public function AddError($err){
		$this->Add($err);
	}

	/** 
	* Adds a query to the debug
	*
	*	@param 	string 	$sql 	query to be debugged
	* 	@param 	string 	$values 	values to be added to the query
	*/
	public function AddQuery($sql, $values){
		if($this->debugType == self::NONE || !$this->queries) return;
		$logThis = "query run: [".$sql."]";
		if(!is_null($values)){
			$values = print_r($values, true);
			$logThis .= " - values: [".$values."]";
		}
		$this->Add($logThis);
	}

	/**
	 * Adds an error to the debugger
	 * @param Exception		$err 	Exception
	 */
	public function Error($err){
		$this->Add($err->getMessage());
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
		if(is_a($deb, "MagratheaException")){
			$html .= "<div style='padding: 10px; border: 1px dotted black; margin-bottom: 5px; line-height: 120%;'>";
			$html .= "<span style='color: red; font-weight: bold;'>".get_class($deb)."</span><br/>";
			$html .= "<span>".$deb->getMessage()."</span><br/>";
			$html .= "<span style='padding-left: 20px;'><b>at: </b> ==> ";
			$html .= "<b>File: </b>".$deb->getFile()." / <b>line: </b>[".$deb->getLine()."]</span><br/>";
			$html .= "<div style='padding-left: 20px;'><b>trace: </b> ==> <br/>";
			$html .= "<div style='padding-left: 10px;'>".$this->printTrace($deb->getTrace())."</div></div>";
			$html .= "</div>";
		} else if (is_array($deb)) {
			$html .= "<div style='padding: 10px; border: 1px dotted black; margin-bottom: 5px; line-height: 120%;'>";
			$html .= "<span>".nice_p_r($deb)."</span><br/>";
			$html .= "</div>";
		} else {
			$html .= "<div style='padding: 10px; border: 1px dotted black; margin-bottom: 5px; line-height: 120%;'>";
			$html .= "<span>".p_r($deb)."</span><br/>";
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