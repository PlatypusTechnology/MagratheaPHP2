<?php

namespace Magrathea2\Compressors;

#######################################################################################
####
####    MAGRATHEA COMPRESSOR PARENT
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2023-12 by Paulo Martins
####
#######################################################################################
class MagratheaCompressor {
	protected $files = [];
	protected $compress = false;

	/**
	 * add a file path
	 * @param 	string 	$file		js file path
	 * @return 	MagratheaCompressor itself
	*/
	public function AddFile(string $file): MagratheaCompressor {
		array_push($this->files, $file);
		return $this;
	}
	/**
	 * add an array of file paths
	 * @param 	array 	$file		js file path
	 * @return 	MagratheaCompressor itself
	*/
	public function AddArray(array $fs): MagratheaCompressor {
		array_push($this->files, ...$fs);
		return $this;
	}

	/**
	 * return minified code
	 * @return string	code
	 */
	public function GetCode(): string {
		return $this->GetMinCode();
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

	/**
	 * return minified code
	 * @return string	code
	 */
	public function GetMinCode(): string {
		throw new \Exception("Parent Compressor does not minify code");
	}


}

