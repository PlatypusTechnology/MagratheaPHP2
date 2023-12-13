<?php

namespace Magrathea2\Compressors;

#######################################################################################
####
####    MAGRATHEA JS COMPRESSOR
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2023-04 by Paulo Martins
####
#######################################################################################
class JavascriptCompressor extends MagratheaCompressor {

	/**
	 * return minified code
	 * @return string	code
	 */
	public function GetMinCode(): string {
		$minifiedCode = "";
		foreach($this->files as $f) {
			$minifiedCode .= \JShrink\Minifier::minify(file_get_contents($f));
		}
		return $minifiedCode;
	}

}

