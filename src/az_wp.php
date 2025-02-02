<?php

namespace AzUtils;

use AzUtils\classes\AZ_DataClass;

class az_wp
{
	private static $cached_dirs = [];

	static function getUrl($params = [])
	{
		return (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	}


	public static function getPluginDir($cache_dir = '')
	{
		$cache_name = empty($cache_dir) ? __FILE__ : $cache_dir;
		/**
		 * if we cached the result for the current file we can use it.
		 */
		if (isset(self::$cached_dirs[$cache_name]))
			return self::$cached_dirs[$cache_name];

		$path = str_replace('/', '\\', plugin_dir_path(__FILE__));
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
	static function getMetaListOf($search, array $key_list): array
	{
		if (is_a($search, 'WP_Post')) $search = $search->ID;
		$res = [];
		foreach ($key_list as $key) {
			$res[$key] = self::getMetaOf($search, $key);
		}
		return $res;
	}
	static function getMetaOf($search, string $key)
	{
		if (
			is_object($search)
			&& property_exists($search, 'field')
			&& is_a($search->field, 'WP_Post')
		) {
			$search = $search->field;
		}

		if (is_a($search, 'WC_Order_Item'))
			return $search->get_meta($key);

		if (
			is_a($search, 'WP_Post')
			|| (is_object($search)
				&& property_exists($search, 'ID'))
		) {
			$search = $search->ID;
		}

		assert(!is_int($search), 'could not load the post id to get its meta');
		assert(function_exists('get_post_meta'), 'get_post_meta function is not defined. are you in a wordpress environment?');
		return get_post_meta(
			$search,
			$key,
			true
		);
	}
	static function getMetaBoolOf($search, string $key): bool
	{
		$meta_value = self::getMetaOf(
			$search,
			$key
		);
		if (empty($meta_value)) return false;
		return filter_var(
			$meta_value,
			FILTER_VALIDATE_BOOL
		);
	}
	static function getMetaNumericOf($search, string $key, int $default = -1): int
	{
		$meta_value = self::getMetaOf(
			$search,
			$key
		);
		if (is_numeric($meta_value)) return intval($meta_value);
		return $default;
	}
}
