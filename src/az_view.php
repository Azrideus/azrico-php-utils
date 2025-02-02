<?php

namespace AzUtils;

use AzUtils\az_wp;

abstract class az_view
{
	/**
	 * a reference to any path in the current plugin
	 * this is needed so we know where to load view files from 
	 */
	abstract public static function getPath();

	private final static function view($view_name, $model = null)
	{
		/**
		 * get a reference to any path in the current plugin
		 * this is needed so we know where to load view files from 
		 */
		$plugin_path = static::getPath();
		assert(!empty($plugin_path), 'getPath() is not implemented correctly');
		assert(file_exists($plugin_path), 'plugin_path does not exist');

		/**
		 * get root path of the plugin,
		 * this is where the src/views folder is located
		 */
		$rootpath = az_wp::getPluginDir($plugin_path);
		assert(file_exists($rootpath), 'rootpath does not exist');

		$rootpath = $rootpath . 'src/views/';
		assert(file_exists($rootpath), 'rootpath does not have the src/views folder');

		//load the file
		$view_full_path = untrailingslashit($rootpath . $view_name . '.php');
		if (file_exists($view_full_path)) {
			ob_start();
			include $view_full_path;
			return ob_get_clean();
		} else {
			error_log('az_views: file not found: ' . $view_full_path);
		}
		return '';
	}

	final static function frontend($dir, $model = null)
	{
		return static::view("frontend/" . $dir, $model);
	}
	final static function backend($dir, $model = null)
	{
		return static::view("backend/" . $dir, $model);
	}
	final static function shared($dir, $model = null)
	{
		return static::view("shared/" . $dir, $model);
	}

	/**
	 * make an element with given attributes 
	 */
	public static function element(string $tag, callable|string $content = '',  ...$rest_attr)
	{
		$attr = static::attr(...$rest_attr);
		if (\is_callable($content)) $content = $content();
		return "<$tag $attr> $content </$tag>";
	}
	static function attr(...$params)
	{

		$attr_str = '';
		$final_list = [];

		foreach ($params as $key => $check_item) {
			if (is_string($check_item)) {
				/* --------------------- attribute is given as a string --------------------- */
				array_push($final_list[], $check_item);
			} else {
				/* --------------------------- attr given as array -------------------------- */
				foreach ($check_item as $sub_key => $value) {
					$fixed_value = $value;

					if (is_bool($fixed_value)) {
						if (false == $fixed_value) continue; //dont add false values
						$fixed_value =  "true";
					} else if (is_int($fixed_value)) {
						//add int values as string 
						$fixed_value =  strval($fixed_value);
					}

					if (empty($fixed_value)) {
						continue; //dont add empty values
					}

					if (empty($final_list[$sub_key])) $final_list[$sub_key] = [];
					array_push($final_list[$sub_key], $fixed_value);
				}
			}
		}

		foreach ($final_list as $sub_key => $value) {
			if (is_numeric($sub_key) && is_string($value)) {
				$attr_str .= ' ' . $value;
			} else {
				$attr_str .= ' ' . $sub_key;
				$attr_str .= "='";
				$attr_str .= trim(join(" ", array_unique($value)));
				$attr_str .= "'";
			}
		}
		return trim($attr_str);
	}
}
