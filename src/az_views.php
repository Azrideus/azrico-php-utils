<?php

namespace AzUtils;

use AzUtils\az_wp;

class az_views
{
	private static function view($dir, $model = null)
	{
		ob_start();
		$root_dir = az_wp::getPluginDir();
		$dir = untrailingslashit($root_dir . 'src/views/' . $dir . '.php');
		if (file_exists($dir))
			include $dir;
		else error_log('File not found: ' . $dir);

		return ob_get_clean();
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
