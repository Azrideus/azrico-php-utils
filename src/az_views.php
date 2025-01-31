<?php

namespace AzUtils;

use AzUtils\az_wp;

class az_views
{
	private static function view($dir, $model = null)
	{
		$root_dir = az_wp::getPluginDir();
		$dir = untrailingslashit($root_dir . 'src/views/' . $dir . '.php');
		if (file_exists($dir)) {
			ob_start();
			include $dir;
			return ob_get_clean();
		} else error_log('File not found: ' . $dir);
		return '';
	}
	static function frontend($dir, $model = null)
	{
		return self::view("frontend/" . $dir, $model);
	}
	static function backend($dir, $model = null)
	{
		return self::view("backend/" . $dir, $model);
	}
	static function shared($dir, $model = null)
	{
		return self::view("shared/" . $dir, $model);
	}
}
