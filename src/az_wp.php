<?php

namespace AzUtils;

use AzUtils\classes\AZ_DataClass;
use AzUtils\wp\az_wp_post_meta;
use AzUtils\wp\az_wp_post;

class az_wp
{
	use az_wp_post_meta;
	use az_wp_post;
	private static $cached_dirs = [];

	static function getUrl($params = [])
	{
		return (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	}


	/**
	 * get plugin dir path based on a given file path of the plugin 
	 */
	public static function getPluginDir(string $file_path = '')
	{
		$cache_name = empty($file_path) ? __FILE__ : $file_path;
		/**
		 * if we cached the result for the current file we can use it.
		 */
		if (isset(self::$cached_dirs[$cache_name])) {
			return self::$cached_dirs[$cache_name];
		}

		$path = str_replace('/', '\\', plugin_dir_path($file_path));
		$plugin_dir_parts = explode("\\", str_replace('/', '\\', WP_PLUGIN_DIR));
		$last_part = end($plugin_dir_parts);

		$two_parts_of_path = explode($last_part, $path);
		$relative_plugin_dir = trim(end($two_parts_of_path), '\\');

		$plugin_name = explode("\\", $relative_plugin_dir)[0];

		$temp_dir
			= WP_PLUGIN_DIR . '\\' . $plugin_name . '\\';

		assert(file_exists($temp_dir), 'failed to get plugin dir');


		self::$cached_dirs[$cache_name] =	$temp_dir;
		return $temp_dir;
	}
}
