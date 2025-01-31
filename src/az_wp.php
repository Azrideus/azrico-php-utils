<?php

namespace AzUtils;

class az_wp
{
	private static $cached_dir = null;
	static function getUrl($params = [])
	{
		return (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	}


	public static function getPluginDir()
	{
		/**
		 * each plugin has its own instance of this class so we can cache the result directly
		 */
		if (null != self::$cached_dir) return self::$cached_dir;

		$path = str_replace('/', '\\', plugin_dir_path(__FILE__));
		$plugin_dir_parts = explode("\\", str_replace('/', '\\', WP_PLUGIN_DIR));
		$last_part = end($plugin_dir_parts);

		$two_parts_of_path = explode($last_part, $path);
		$relative_plugin_dir = trim(end($two_parts_of_path), '\\');

		$plugin_name = explode("\\", $relative_plugin_dir)[0];

		$temp_dir
			= WP_PLUGIN_DIR . '\\' . $plugin_name . '\\';
		assert(file_exists($temp_dir), 'failed to get plugin dir');

		self::$cached_dir =	$temp_dir;
		return self::$cached_dir;
	}
}
