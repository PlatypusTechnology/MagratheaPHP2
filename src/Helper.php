<?php

namespace Magrathea2;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Helper created: 2022-11 by Paulo Martins
####
#######################################################################################
class Helper {

	/**
	*	Generates a random string
	*	@param 		integer 		$length 	size of string
	*	@return 	string 			random string
	*/
	public static function RandomString($length = 10): string {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	/**
	 * Converts HEXrgb to DECrgb (#CB8008) to [r:203, g:128, b:8]
	 * @param string $hexColor		hexadecimal color
	 * @return array			decimal color as [r, g, b]
	 */
	function HexToRgb($hex): array {
		$hex = str_replace('#', '', $hex);
		if (strlen($hex) == 3) {
			$hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
		}
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
	
		return array('r' => $r, 'g' => $g, 'b' => $b);
	}
	

}
