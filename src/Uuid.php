<?php
namespace Magrathea2;

class Uuid {

	/**
	 * Generates a UUIDv7 (RFC 9562): 48-bit ms timestamp + version 7 + variant bits + random.
	 * @return 	string 		UUIDv7, lowercase, hyphenated
	 */
	public static function V7(): string {
		$ms = (int) (microtime(true) * 1000);

		$timeHex = str_pad(dechex($ms), 12, "0", STR_PAD_LEFT);

		$rand = random_bytes(10);

		// version nibble (7) into byte 0 of random part (maps to the version position after hex assembly)
		$rand[0] = chr((ord($rand[0]) & 0x0f) | 0x70);
		// variant bits (10) into byte 2 of random part
		$rand[2] = chr((ord($rand[2]) & 0x3f) | 0x80);

		$randHex = bin2hex($rand);

		return sprintf(
			"%s-%s-%s-%s-%s",
			substr($timeHex, 0, 8),
			substr($timeHex, 8, 4),
			substr($randHex, 0, 4),
			substr($randHex, 4, 4),
			substr($randHex, 8, 12)
		);
	}
}
