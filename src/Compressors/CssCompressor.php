<?php

namespace Magrathea2\Compressors;

#######################################################################################
####
####    MAGRATHEA CSS COMPRESSOR
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2023-12 by Paulo Martins
####
#######################################################################################
class CssCompressor extends MagratheaCompressor {

	/**
	 * return minified code
	 * @return string	code
	 */
	public function GetMinCode(): string {
		$compiler = new \ScssPhp\ScssPhp\Compiler();
		return $compiler
			->compileString($this->GetRawCode())
			->getCss();
	}
}

