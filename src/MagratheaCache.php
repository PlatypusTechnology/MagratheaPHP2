<?php

namespace Magrathea2;

use Magrathea2\Admin\AdminManager;
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
	private $extension = "json";
	private bool $bypass = false;

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

	private function CreateHandle(string $name, $data=null): string {
		if(is_object($data)) {
			throw new MagratheaException("cache handle cannot be an object!");
		}
		if(!empty($data)) { $name = $name."-".$data; }
		$name = str_replace('/', '|', $name);
		$name = str_replace('\\', '|', $name);
		return $name;
	}

	/**
	 * Clears cache for given cache name.
	 * Doesn't change the current cache handle (set by `Cache()`).
	 * @param		$name		cached name
	 * @param		$data		(optional) reference to identify cached data
	 */
	public function Clear(string $name, $data=null) {
		$file = $this->GetFileForHandle($this->CreateHandle($name, $data));
		if(!file_exists($file)) return true;
		return unlink($file);
	}

	/**
	 * Initiates the cache and displays the cached data if already available (killing the execution)
	 * @param		$name		cached name
	 * @param		$data		(optional) reference to identify cached data
	 */
	public function Cache(string $name, $data=null) {
		if($this->bypass) return false;
		$cacheActive = Config::Instance()->Get("no_cache");
		if(!empty($cacheActive) && $cacheActive == true) return false;
		$this->cacheName = $this->CreateHandle($name, $data);
		$this->saveCache = true;
		$this->LookForFile();
	}
	/**
	 * Turns caching off (or back on): while bypassed, `Cache()` neither serves nor saves, as with `no_cache`.
	 * `MagratheaApi::ExecuteUrl()` bypasses the cache in raw mode.
	 * @param			bool		bypass
	 * @return		MagratheaCache		itself
	 */
	public function Bypass(bool $bypass=true): MagratheaCache {
		$this->bypass = $bypass;
		if($bypass) $this->saveCache = false;
		return $this;
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
		return $this->GetFileForHandle($this->cacheName);
	}
	private function GetFileForHandle(?string $handle): string {
		$path = $this->GetCachePath();
		$fileName = $handle.".".$this->extension;
		return MagratheaHelper::EnsureTrailingSlash($path).$fileName;
	}
	/**
	 * Saves cache file info.
	 * Writes to a temp file in the cache dir and renames it over the cache file,
	 * so concurrent readers never see an empty or partial file.
	 * A process killed mid-write can leave a `*.tmp` file behind; `RemoveAllCache()` deletes it.
	 * @param 		string 		info to cache
	 * @return 		int|false	bytes written, or false if the file could not be written
	 */
	public function SaveFile(string $data) {
		$filePath = $this->GetCacheFile();
		// not tempnam(): it creates files as 0600, unreadable when web server and CLI are different users
		$tmpPath = $filePath.".".getmypid().".".bin2hex(random_bytes(6)).".tmp";
		$f = @fopen($tmpPath, 'x');
		if($f === false) return false;
		$written = @fwrite($f, $data);
		$closed = @fclose($f);
		if($written !== strlen($data) || !$closed || !@rename($tmpPath, $filePath)) {
			@unlink($tmpPath);
			return false;
		}
		return $written;
	}
	/**
	 * Deletes a file from cache
	 * @param 	string		file name
	 */
	public function DeleteFile(string $file, bool $addExtension = true) {
		$path = $this->GetCachePath();
		if($addExtension) $file = $file.".".$this->extension;
		$filePath = MagratheaHelper::EnsureTrailingSlash($path).$file;
		AdminManager::Instance()->Log("deleting cache", $file, ["path" => $filePath]);
		Logger::Instance()->Log("cache deleting: [".$filePath."]");
		return @unlink($filePath);
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
	 * Handles data from the API to save it on cache and displays it, killing the execution.
	 * The DEV `_debug` items (`Debugger::WithCaptured()`) are added to the response, never to the cache file.
	 */
	public function HandleApiCache(array $data) {
		if(!$this->saveCache) return false;
		if(empty($this->cacheName)) return false;
		$data["cached"] = true;
		$data["cached_time"] = now();
		$this->SaveFile(json_encode($data));
		$data["cached"] = false;
		$this->ShowJson(Debugger::Instance()->WithCaptured($data));
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
