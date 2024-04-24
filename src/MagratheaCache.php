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
		$this->cachePath = realpath($path);
		if(!$this->cachePath) {
			throw new MagratheaException("cache path is invalid");
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

	/**
	 * Initiates the cache and displays the cached data if already available (killing the execution)
	 * @param		$name		cached name
	 * @param		$data		(optional) reference to identify cached data
	 */
	public function Cache(string $name, $data=null) {
		if(is_object($data)) {
			throw new MagratheaException("cache handle cannot be an object!");
		}
		if(!empty($data)) { $name = $name."-".$data; }
		$this->cacheName = $name;
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
		return file_put_contents($this->GetCacheFile(), $data);
	}

	/**
	 * Handles data from the API to save it on cache and displays it, killing the execution
	 */
	public function HandleApiCache(array $data) {
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
