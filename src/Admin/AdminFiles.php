<?php

namespace Magrathea2\Admin;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    AdminFiles created: 2026-10 by Paulo Martins
####
#######################################################################################

/**
 * File checks for the admin's views (logs, backups)
 */
class AdminFiles {

	/**
	 * Resolves a file name inside a folder: `$name` must be a plain name (a string, `basename($name) === $name`,
	 * no `..`, `\` or NUL) and its `realpath()` an existing regular file inside `realpath($folder)`
	 * (so `../` and symlinks pointing outside are rejected).
	 * @param 	mixed 	$folder		folder
	 * @param 	mixed 	$name			file name (usually from the request)
	 * @return 	string|null			full path of the file, or null
	 */
	public static function ResolveInFolder($folder, $name): ?string {
		if(!is_string($folder) || $folder === "") return null;
		if(!is_string($name) || $name === "" || basename($name) !== $name
			|| str_contains($name, "..") || str_contains($name, "\\") || str_contains($name, "\0")) {
			return null;
		}
		$base = realpath($folder);
		if(!$base || !is_dir($base)) return null;
		$path = realpath($base.DIRECTORY_SEPARATOR.$name);
		if(!$path || !is_file($path)) return null;
		if(!str_starts_with($path, rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) return null;
		return $path;
	}

}
