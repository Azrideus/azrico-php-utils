<?php

namespace AzUtils;

use AzUtils\az_wp;

class az_views
{
	private static function view($dir, $model = null)
	{
		$root = az_wp::getPluginDir(__FILE__);
		$dir = untrailingslashit($root . 'src/views/' . $dir . '.php');
		if (file_exists($dir)) {
			ob_start();
			include $dir;
			return ob_get_clean();
		} else {
			error_log('az_views: file not found: ' . $dir);
		}
		return '';
	}

	static function frontend($dir, $model = null)
	{
		return static::view("frontend/" . $dir, $model);
	}
	static function backend($dir, $model = null)
	{
		return static::view("backend/" . $dir, $model);
	}
	static function shared($dir, $model = null)
	{
		return static::view("shared/" . $dir, $model);
	}
}
