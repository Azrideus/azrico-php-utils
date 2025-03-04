<?php

namespace AzUtils\string;

trait az_string_path
{

	static function sanitize_filename($file, $strict = false)
	{
		// Remove anything which isn't a word, whitespace, number
		// or any of the following caracters -_~,;[]().
		// If you don't need to handle multi-byte characters
		// you can use preg_replace rather than mb_ereg_replace
		// Thanks @Łukasz Rysiak!
		$file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $file);
		// Remove any runs of periods (thanks falstro!)
		$file = mb_ereg_replace("([\.]{2,})", '', $file);
		if ($strict) {
			$file = mb_ereg_replace("(^\.)|\s", '', $file);
		}
		$file = \substr($file, 0, 255);
		return $file;
	}
	static function sanitize_foldername($folder)
	{
		$file = self::sanitize_filename($folder);
		// Remove any runs of periods (thanks falstro!)
		$file = mb_ereg_replace("([\.]{2,})", '', $file);
		return $file;
	}
	static function fix_path($p, $sep = '')
	{
		if (empty($sep)) $sep = DIRECTORY_SEPARATOR;
		$p = str_replace('/', $sep, $p);
		$p = str_replace('\\', $sep, $p);
		return $p;
	}
	private static function join_trim(array $parts, $sep = '')
	{
		$joined = self::fix_path(join($sep, $parts), $sep);

		//remove duplicate seperator 
		$regex_pattern = '/\\' . $sep . '+/';
		$joined = preg_replace($regex_pattern, $sep, $joined);

		if ($sep == '/') {
			// fix https://
			$joined = preg_replace('/(https?:\/+)/i', ('$1' . $sep), $joined);
		}
		//remove trailing seperators
		$joined = \rtrim($joined, $sep);

		return $joined;
	}
	static function join_paths()
	{
		$paths = array();
		foreach (func_get_args() as $arg) {
			if ($arg !== '')
				$paths[] = $arg;
		}
		return self::join_trim($paths, DIRECTORY_SEPARATOR);
	}
	static function join_url()
	{
		$paths = array();
		foreach (func_get_args() as $arg) {
			if ($arg !== '')
				$paths[] = $arg;
		}
		return self::join_trim($paths, "/");
	}
}
