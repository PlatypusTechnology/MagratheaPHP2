<?php

namespace Magrathea2\Admin;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    created: 2022-12 by Paulo Martins
####
#######################################################################################

use Magrathea2\Exceptions\MagratheaException;
use Exception;
use Magrathea2\Admin\ObjectManager;

/**
 * Class for handling automatically generated files
 */
class CodeManager extends \Magrathea2\Singleton { 

	/**
	 * @param string $object 	object name
	 * @return array files that will be generated ["file-name", "file-desc", "gen-function"]
	*/
	public function GetFileList($object): array {
		return [
			"model-base" => [
				"file-name" => $object."Base.php",
				"file-desc" => $object." Base Code",
				"type" => "model-base",
				"gen-function" => "GetCodeForObjectBase"
			],
			"control-base" => [
				"file-name" => $object."ControlBase.php",
				"file-desc" => $object." Control Base Code",
				"type" => "control-base",
				"gen-function" => "GetCodeForObjectControlBase"
			],
			"model" => [
				"file-name" => $object.".php",
				"file-desc" => $object." Model Code",
				"type" => "model",
				"gen-function" => "GetCodeForObject"
			],
			"control" => [
				"file-name" => $object."Control.php",
				"file-desc" => $object." Model Control Code",
				"type" => "control",
				"gen-function" => "GetCodeForControl"
			],
			"admin" => [
				"file-name" => $object."Admin.php",
				"file-desc" => $object." CRUD Admin",
				"type" => "admin",
				"gen-function" => "GetCodeForAdmin"
			],
			"api" => [
				"file-name" => $object."Api.php",
				"file-desc" => $object." API",
				"type" => "api",
				"gen-function" => "GetCodeForApi"
			],
		];
	}

	/**
	 * @param 	string 	$object	object name
	 * @return 	string	code
	 */
	public function GetCodeForObjectBase($object): string {
		$objectData = ObjectManager::Instance()->GetObjectData($object);
		return CodeCreator::Instance()->Load()->GenerateBaseCodeForObject($object, $objectData);
	}
	/**
	 * @param 	string 	$object	object name
	 * @return 	string	code
	 */
	public function GetCodeForObjectControlBase($object): string {
		$objectData = ObjectManager::Instance()->GetObjectData($object);
		return CodeCreator::Instance()->Load()->GenerateBaseCodeForObjectControl($object, $objectData);
	}
	/**
	 * @param 	string 	$object	object name
	 * @return 	string	code
	 */
	public function GetCodeForObject($object): string {
		return CodeCreator::Instance()->Load()->GenerateCodeForObject($object);
	}
	/**
	 * @param 	string 	$object	object name
	 * @return 	string	code
	 */
	public function GetCodeForControl($object): string {
		return CodeCreator::Instance()->Load()->GenerateCodeForObjectControl($object);
	}

	public function GetCodeForAdmin($object): string {
		return CodeCreator::Instance()->Load()->GenerateCodeForAdmin($object);
	}

	public function GetCodeForApi($object): string {
		return CodeCreator::Instance()->Load()->GenerateCodeForApi($object);
	}

	private function GetFileDestination($type, $destinationPath, $fileData): string {
		$fileName = $fileData["file-name"];
		if($type == "feature") {
			if($fileData["type"] == "model-base" || $fileData["type"] == "control-base") {
				return $destinationPath."/Base/".$fileName;
			}
			return $destinationPath."/".$fileName;
		}
		if($type == "mvc") {
			switch($fileData["type"]) {
				case "model": return $destinationPath."/Models/".$fileName;
				case "model-base": return $destinationPath."/Models/Base/".$fileName;
				case "control": return $destinationPath."/Controls/".$fileName;
				case "control-base": return $destinationPath."/Controls/Base/".$fileName;
			}
		}
		return $destinationPath."/".$fileName."/default";
	}

	/**
	 * @param 	string 	$object	object name
	 * @return 	array		["code", "overwritable", "file-exists", "file-destination"]
	 */
	public function PrepareCodeFileGeneration($type, $object): array {
		$list = $this->GetFileList($object);
		$fileData = @$list[$type];
		if (!$type || !$fileData) {
			throw new MagratheaException("incorrect type for code generation: [".$type."]");
		}
		$structureData = $this->PrepareStructureForCodeGeneration($object);
		$fileDestination = $this->GetFileDestination($structureData["type"], $structureData["code-destination"], $fileData);
		$fn = $fileData["gen-function"];
		$code = $this->$fn($object);
		return [
			"code" => $code,
			"file-exists" => file_exists($fileDestination),
			"overwritable" => ($type == "model-base" || $type == "control-base") ? true : false,
			"file-destination" => $fileDestination,
		];
	}

	/**
	 * @param 	string 		$type 		file type ("model-base", "control-base", "model", "control")
	 * @param 	string		$obj			object name
	 * @return 	array			[ ["success", "file-name", "error?"]* ]
	 */
	public function WriteCodeFile($type, $obj) {
		$fileData = $this->PrepareCodeFileGeneration($type, $obj);
		$file = $fileData["file-destination"];
		$content = $fileData["code"];
		$overwrite = $fileData["overwritable"];
		try {
			$w = $this->WriteFile($file, $content, $overwrite);
		} catch(Exception $ex) {
			throw $ex;
		}
		$rs = [
			"success" => $w["success"],
			"overwrite" => @$w["overwrite"],
			"type" => $type,
			"file-name" => $file,
		];
		if(!$w["success"]) $rs["error"] = $w["error"];
		return $rs;
	}

	/**
	 * Get paths where code will be written
	 * @param string $type			"feature" or "mvc"
	 * @param string $path			main path for object
	 * @return array ("type", "app", "paths" => "{code-file-type}" => $path);
	 */
	public function GetPaths($type, $mainPath) {
		$paths = [];
		if($type == "feature") {
			$paths["object"] = $mainPath;
			$paths["base"] = $mainPath."/Base";
		} else if($type == "mvc") {
			$paths["models"] = $mainPath."/Models";
			$paths["models-base"] = $mainPath."/Models/Base";
			$paths["controls"] = $mainPath."/Controls";
			$paths["controls-base"] = $mainPath."/Controls/Base";
		}
		return $paths;
	}

	/**
	 * Get data for generating a code file
	 * @param			string			object name
	 * @return 		array				data
	 */
	public function PrepareStructureForCodeGeneration($object) {
		$codeData = CodeCreator::Instance()->GetCodeCreationData();
		if(!$codeData["success"]) {
			throw new MagratheaException("Can not create code for ".$object, 400);
		}
		$rs = [];
		$data = $codeData["data"];
		$codePath = $data["code-path"];
		$rs["type"] = $data["code-structure"];
		$rs["namespace"] = $data["code-namespace"];
		if($rs["type"] == "feature") {
			$rs["code-destination"] = $codePath."/features/".$object;
		}
		if($rs["type"] == "mvc") {
			$rs["code-destination"] = $codePath;
		}
		$rs["paths"] = $this->GetPaths($rs["type"], $rs["code-destination"]);
		$rs["code-path"] = $codePath;
		$rs["ready"] = $this->AreAllPathsWritable($rs["paths"]);
		return $rs;
	}

	/**
	 * checks if all paths are writable (thanks, ChatGPT)
	 * @param array $paths
	 * @return bool
	 */
	private function AreAllPathsWritable($paths) {
    foreach ($paths as $path) {
			if (!is_writable($path)) return false;
    }
    return true;
	}

	/**
	 * prepares a folder to receive a code file.
	 * it creates the folder (or returns true if it already exists) and checks if it is writable
	 * @param string $path		path
	 * @return array		('created', 'exists', 'writable')
	 */
	private function PrepareFolderPath($path) {
		$data = [];
		$data["path"] = $path;
		if(!is_dir($path)){
			$data["created"] = @mkdir($path);
		} else {
			$data["exists"] = true;
		}
		$data["writable"] = is_writable($path);
		return $data;
	}

	/**
	 * Create Folders for object
	 * @param			string			object name
	 * @return 		array				data ["success", "data"]
	 */
	public function PrepareFolders($object) {
		$paths = $this->PrepareStructureForCodeGeneration($object);
		$rs = [];

		$rs["type"] = $paths["type"];
		$rs["app"] = $this->PrepareFolderPath($paths["code-path"]);
		foreach($paths["paths"] as $file => $path) {
			$rs[$file] = $this->PrepareFolderPath($path);
		}
		return $rs;
	}

	/**
	 * Writes a file with some content (code, please)
	 * @param		string 		$file				file that will be written
	 * @param		string		$content		content to be written
	 * @param 	boolean 	$overwrite	should we overwrite whater is in the file?
	 * @return 	array			["success", "error?", "data?"]
	 */
	function WriteFile($file, $content, $overwrite=false){
		$file_existed = false;
		if(file_exists($file)){
			$file_existed = true;
			if($overwrite) {
				unlink($file);
			} else {
				return ["success" => false, "error" => "file already exists"];
			}
		}
		if (!$handle = @fopen($file, 'w')) { 
			return ["success" => false, "error" => "could not open file", "data" => $handle];
		} 
		if (!fwrite($handle, $content)) { 
			return ["success" => false, "error" => "could not write file", "data" => $handle];
		}
		AdminManager::Instance()->Log("code file written", $file);
		fclose($handle); 
		return ["success" => true, "overwrite" => $file_existed];
	}

}
