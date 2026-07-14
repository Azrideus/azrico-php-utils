<?php

namespace AzUtils;

use AzUtils\classes\AZ_DataClass;
use AzUtils\wp\az_wp_external_post;
use AzUtils\wp\az_wp_post_meta;
use AzUtils\wp\az_wp_post;
use AzUtils\wp\az_wp_plugin;
use AzUtils\wp\az_wp_terms;
use AzUtils\wp\az_wp_orders;
use AzUtils\wp\az_wp_links;
use AzUtils\wp\az_wp_error;
use AzUtils\wp\az_wp_searchquery;
use AzUtils\wp\az_wp_related;
use AzUtils\wp\az_wp_related_category_extra;
use AzUtils\wp\az_wp_related_sorting;

class az_wp
{
	use az_wp_searchquery;
	use az_wp_post_meta;
	use az_wp_post;
	use az_wp_external_post;
	use az_wp_plugin;
	use az_wp_terms;
	use az_wp_links;
	use az_wp_orders;
	use az_wp_error;
	use az_wp_related;
	use az_wp_related_category_extra;
	use az_wp_related_sorting;

	private static $cached_dirs = [];
	public static $DEBUG = false;



	/**
	 * get plugin base url path based on a given file path
	 */
	public static function getPluginUrl(string $file_path = '', string $join_url = '')
	{
		$cache_name = "url__" . (empty($file_path) ? __FILE__ : $file_path);

		if (!isset(self::$cached_dirs[$cache_name])) {
			/**
			 * plugin_dir_url() expects a file in the plugin to get the plugin url
			 * so we cant directly give output of getPluginDir() to it
			 */
			$file_in_plugin = self::getPluginDir($file_path) . '\\index.php';
			self::$cached_dirs[$cache_name] = plugin_dir_url($file_in_plugin);
		}
		return az_string::join_url(self::$cached_dirs[$cache_name], $join_url);
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
		if (!isset(self::$cached_dirs[$cache_name])) {
			$path = az_string::fix_path(plugin_dir_path($file_path));
			$plugin_dir_parts = explode(DIRECTORY_SEPARATOR, az_string::fix_path(WP_PLUGIN_DIR));

			$last_part = end($plugin_dir_parts);

			$two_parts_of_path = explode($last_part, $path);
			$relative_plugin_dir = trim(end($two_parts_of_path), DIRECTORY_SEPARATOR);

			$plugin_name = explode(DIRECTORY_SEPARATOR, $relative_plugin_dir)[0];

			$temp_dir = az_string::join_paths(WP_PLUGIN_DIR, $plugin_name);
			assert(file_exists($temp_dir), 'failed to get plugin dir');
			self::$cached_dirs[$cache_name] =	$temp_dir;
		}

		return self::$cached_dirs[$cache_name];
	}

	public static function getPluginMainFile(string $plugin_file = '')
	{
		$cache_name = (empty($file_path) ? __FILE__ : $file_path) . "__main_file";
		if (!isset(self::$cached_dirs[$cache_name])) {
			$pdir = az_wp::getPluginDir($plugin_file);
			$files = az_assets::get_files_in($pdir, '.php');
			foreach ($files as $f) {
				$plugin_data = get_file_data($f, ['Version' => 'Version']);
				if ($plugin_data['Version']) {
					self::$cached_dirs[$cache_name] = $f;
					break;
				}
			}
		}

		return self::$cached_dirs[$cache_name];
	}
	/**
	 * get plugin dir path based on a given file path of the plugin 
	 */
	public static function getPluginName(string $file_path = '')
	{
		$path = self::getPluginDir($file_path);
		$plugin_name = explode(DIRECTORY_SEPARATOR, $path);
		$plugin_name = end($plugin_name);
		return $plugin_name;
	}
	public static function is_localhost($whitelist = ['127.0.0.1', '::1'])
	{
		return $_SERVER['HTTP_HOST'] == 'localhost' ||
			in_array($_SERVER['REMOTE_ADDR'], $whitelist);
	}
}
