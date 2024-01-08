<?php

namespace Magrathea2;
use Magrathea2\Singleton;
use Magrathea2\Exceptions\MagratheaException;
use Magrathea2\Exceptions\MagratheaConfigException;
use Magrathea2\Exceptions\MagratheaDBException;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################


/**
 * Magrathea class for logging anything
 * By default, the message is written with a timestamp before it.
 * 		- For *Log* function, the default file is saved with a timestamp in the name
 * 		- For *LogError* function, by default, all the data is saved in a same file called *log_error.log*
 */
class Logger extends Singleton {

	private $logPath;
	private $activeLogFile;

	public function Initialize(): void {
	}

	/**
	 * Gets active log file
	 * @return string	log path
	 */
	public function GetFullLogFile(): string {
		$this->CheckLogFileIsNull();
		return $this->logPath."/".$this->activeLogFile;
	}

	/**
	 * Sets the active log file
	 * @param string $name
	 * @return Logger		itself
	 */
	public function SetLogFile($name): Logger {
		$this->activeLogFile = $name;
		return $this;
	}
	/**
	 * returns active log file name
	 * @return string		file name
	 */
	public function GetLogFile(): string {
		$this->CheckLogFileIsNull();
		return $this->activeLogFile;
	}
	
	private function StartLogName(): Logger {
		$this->activeLogFile = "log_".@date("Ym").".log";
		return $this;
	}

	/**
	 * loads the path for saving log
	 * @return Logger
	 */
	public function LoadLogsPath(): Logger {
		try {
			$logsPath = Config::Instance()->Get("logs_path");
		} catch(MagratheaConfigException $ex) {
			$magRoot = MagratheaPHP::Instance()->magRoot;
			if(!$magRoot) throw new MagratheaConfigException("logs_path is invalid and mag root is null");
			$logsPath = $magRoot."/logs";
		} catch(\Exception $ex) {
			throw $ex;
		}
		$this->logPath = $logsPath;
		if(!$this->logPath) {
			$this->logPath = MagratheaPHP::Instance()->magRoot."/logs";
		}
		return $this;
	}

	/**
	 * Returns log path
	 * @return 	string 	$logPath
	 */
	public function GetLogPath(): string|null {
		$this->CheckLogFileIsNull();
		return $this->logPath;
	}
	/**
	 * Sets log path
	 * @param string $p		path for the logs folder
	 * @return Logger
	 */
	public function SetLogPath($p): Logger {
		$this->logPath = $p;
		return $this;
	}

	private function CheckLogFileIsNull() {
		if(!$this->logPath) {
			$this->LoadLogsPath();
		}
		if(!$this->activeLogFile) {
			$this->StartLogName();
		}
	}

	/**
	 * Logs a message - any message
	 * @param 	string 		$logThis 	message to be logged
	 * @throws  \Exception 				If path is not writablle
	 */
	public function Log($logThis) {
		echo "logging ".$logThis;
		if( Config::Instance()->GetEnvironment() == "test" ) return;
		if( is_a($logThis, \Magrathea2\Exceptions\MagratheaConfigException::class) ) {
			p_r($logThis);
			echo "==[config not properly set!]==";
			return;
		}
		$date = @date("Y-m-d h:i:s");
		$line = "[".$date."] = ".$logThis."\n";
		$path = $this->GetFullLogFile();
		if(!is_writable($this->logPath)){
			$message = "error trying to save file at [".$path."] - confirm permission for writing";
			$message .= " - - error message: [".$logThis."]";
			throw new \Magrathea2\Exceptions\MagratheaConfigException($message);
		}
		file_put_contents($path, $line, FILE_APPEND);
	}

	/**
	 * Logs an error
	 * @param 	\Exception	 	$logThis 	error to be logged
	 */
	public function LogError($error){
		$date = @date("Y-m-d_his");
		if ($error instanceof MagratheaException) {
			$line = get_class($error)." Catch: [".$date."] = ".$error->getMsg();
			if ($error instanceof MagratheaDBException) {
				$line .= "\n\t ==> SQL: [".$error->query."]";
			}
		} else if ($error instanceof \Exception) {
			$line = "MagratheaError Catch: [".$date."] = ".$error->getMessage();
		}
		return $this->Log($line);
	}
}

?>