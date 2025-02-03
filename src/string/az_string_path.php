<?php

namespace AzUtils\string;

trait az_string_path
{

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
		$joined = preg_replace('#' . $sep . '+#', $sep, $joined);

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
