<?php

namespace Magrathea2;
use Magrathea2\Exceptions\MagratheaException;

#######################################################################################
####
####    MAGRATHEA JS COMPRESSOR
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2023-04 by Paulo Martins
####
#######################################################################################

/**
* 
* Control for Create, Read, List, Update, Delete
**/
class MagratheaJavascriptCompressor {
	private $files = [];
	private $compress = false;

	/**
	 * add a file path
	 * @param 	string 	$file		js file path
	 * @return 	MagratheaJavascriptCompressor itself
	*/
	public function AddFile(string $file): MagratheaJavascriptCompressor {
		array_push($this->files, $file);
		return $this;
	}
	/**
	 * add an array of file paths
	 * @param 	array 	$file		js file path
	 * @return 	MagratheaJavascriptCompressor itself
	*/
	public function AddArray(array $fs): MagratheaJavascriptCompressor {
		array_push($this->files, ...$fs);
		return $this;
	}

	public function GetCode(): string {
		return $this->GetMinCode();
	}

	public function GetMinCode(): string {
		$minifiedCode = "";
		foreach($this->files as $f) {
			$minifiedCode .= \JShrink\Minifier::minify(file_get_contents($f));
		}
		return $minifiedCode;
	}

	/**
	 * Get the raw code from all the files
	 */
	public function GetRawCode(): string {
		$code = "";
		foreach ($this->files as $f) {
			$code .= file_get_contents($f);
		}
		return $code;
	}

}

