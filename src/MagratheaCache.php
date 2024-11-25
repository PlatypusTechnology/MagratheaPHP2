<?php

namespace Magrathea2;

use Magrathea2\Exceptions\MagratheaConfigException;
use Magrathea2\Exceptions\MagratheaException;

/**
 * MagratheaCache:
 * 	cache static responses to save some processing time
 */
class MagratheaCache extends Singleton {

	public $cacheName;
	public bool $saveCache = false;
	private $cachePath;
	private $extension = "txt";

	function Initialize() {
		$this->cacheName = null;
	}

	/**
	 * Loads the cache path from config file
	 * @return 	MagratheaCache 		itself
	 */
	public function LoadCachePath(): MagratheaCache {
		try {
			$path = Config::Instance()->Get("cache_path");
		} catch(\Exception $ex) {
			throw $ex;
		}
		if(!$path) {
			throw new MagratheaException("[cache_path] is empty");
		}
		$this->cachePath = @realpath($path);
		if(!$this->cachePath) {
			throw new MagratheaException("cache path is invalid: ".$path);
		}
		return $this;
	}

	/**
	 * Gets the path where cached files will be stored
	 * @return 		string
	 */
	public function GetCachePath(): string|null {
		if(!$this->cachePath) {
			$this->LoadCachePath();
		}
		return $this->cachePath;
	}

	private function CreateHandle(string $name, $data=null) {
		if(is_object($data)) {
			throw new MagratheaException("cache handle cannot be an object!");
		}
		if(!empty($data)) { $name = $name."-".$data; }
		$name = str_replace('/', '|', $name);
		$name = str_replace('\\', '|', $name);
		$this->cacheName = $name;
	}

	/**
	 * Clears cache for given cache name
	 * @param		$name		cached name
	 * @param		$data		(optional) reference to identify cached data
	 */
	public function Clear(string $name, $data=null) {
		$this->CreateHandle($name, $data);
		$file = $this->GetCacheFile();
		if(!file_exists($file)) return true;
		return unlink($file);
	}

	/**
	 * Initiates the cache and displays the cached data if already available (killing the execution)
	 * @param		$name		cached name
	 * @param		$data		(optional) reference to identify cached data
	 */
	public function Cache(string $name, $data=null) {
		$cacheActive = Config::Instance()->Get("no_cache");
		if(!empty($cacheActive) && $cacheActive == true) return false;
		$this->CreateHandle($name, $data);
		$this->saveCache = true;
		$this->LookForFile();
	}
	/**
	 * Sets the type of cached file (html, json, txt, etc)
	 * @param			string		type
	 * @return		MagratheaCache		itself
	 */
	public function Type(string $t): MagratheaCache {
		$this->extension = $t;
		return $this;
	}

	/**
	 * Checks if the cached file exists.
	 * Displays it if does (and kills execution)
	 */
	public function LookForFile() {
		$file = $this->GetCacheFile();
		if(file_exists($file)) {
			$this->ShowJson(file_get_contents($file));
		}
		return false;
	}

	/**
	 * Get full cache file path
	 * @return 	string		path
	 */
	public function GetCacheFile() {
		$path = $this->GetCachePath();
		$fileName = $this->cacheName.".".$this->extension;
		return MagratheaHelper::EnsureTrailingSlash($path).$fileName;
	}
	/**
	 * Saves cache file info
	 * @param 		string 		info to cache
	 */
	public function SaveFile(string $data) {
		$filePath = $this->GetCacheFile();
		$f=fopen($filePath,'w');
		$success = fwrite($f,$data);
		fclose($f);
		return $success;
	}
	/**
	 * Deletes a file from cache
	 * @param 	string		file name
	 */
	public function DeleteFile(string $file, bool $addExtension = true) {
		$path = $this->GetCachePath();
		if($addExtension) $file = $file.".".$this->extension;
		$filePath = MagratheaHelper::EnsureTrailingSlash($path).$file;
		return unlink($filePath);
	}

	/**
	 * deletes all files from cached path
	 * @return 	array		deleted files
	 */
	public function RemoveAllCache() {
		return $this->RemovePattern('*');
	}

	/**
	 * deletes files with pattern
	 * @return 	array		deleted files
	 */
	public function RemovePattern(string $pattern) {
		$path = $this->GetCachePath();
		$cachePath = MagratheaHelper::EnsureTrailingSlash($path).$pattern;
		$files = glob($cachePath); // get all file names
		$removed = [];
		foreach($files as $file){
			if(is_file($file)) {
				array_push($removed, $file);
				unlink($file); // delete file
			}
		}
		return $removed;
	}

	/**
	 * Handles data from the API to save it on cache and displays it, killing the execution
	 */
	public function HandleApiCache(array $data) {
		if(!$this->saveCache) return false;
		if(empty($this->cacheName)) return false;
		$data["cached"] = true;
		$data["cached_time"] = now();
		$this->SaveFile(json_encode($data));
		$data["cached"] = false;
		$this->ShowJson($data);
	}

	/**
	 * Shows a json and kills execution
	 */
	public function ShowJson(array|string $data) {
		header('Content-Type: application/json');
		if(is_string($data)) echo $data;
		else echo json_encode($data);
		die;
	}

}
