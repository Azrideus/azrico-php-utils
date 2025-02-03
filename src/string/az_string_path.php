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
	static function join_paths()
	{
		$paths = array();
		foreach (func_get_args() as $arg) {
			if ($arg !== '') {
				$paths[] = $arg;
			}
		}
		$joined = self::fix_path(join(DIRECTORY_SEPARATOR, $paths));
		return preg_replace('#' . DIRECTORY_SEPARATOR . '+#', DIRECTORY_SEPARATOR, $joined);
	}
	static function join_url()
	{
		$paths = array();
		foreach (func_get_args() as $arg) {
			if ($arg !== '') {
				$paths[] = $arg;
			}
		}
		$joined = self::fix_path(join("/", $paths), '/');
		return preg_replace('#/+#', "/", $joined);
	}
}
