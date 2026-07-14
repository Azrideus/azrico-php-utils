<?php

namespace AzUtils\wp;

use AzUtils\az_assets;
use AzUtils\az_string;
use AzUtils\az_wp;

trait az_wp_plugin
{

	static $front_folder_conditions = [
		'frontend' => '__return_true',
		'cart' => 'is_cart',
		'checkout' => 'is_checkout',
		'cart_checkout' => [self::class, 'is_cart_checkout'],
	];
	static $admin_folders = ['admin', 'backend'];
	static $shared_folders = ['shared'];
	static $default_js_deps = ['jquery'];


	static function get_plugin_version(
		string $plugin_file,
	) {
		$f = az_wp::getPluginMainFile($plugin_file);
		$plugin_data = get_file_data($f, ['Version' => 'Version']);
		return $plugin_data['Version'];
	}

	static function load_style_folder(
		string $plugin_file,
		string $folder,
		array $deps = [],
		string|int $version = null
	) {
		$use_cahce = !az_wp::is_localhost();

		$pdir = az_wp::getPluginDir($plugin_file);
		$purl = az_wp::getPluginUrl($plugin_file);
		$file_path = az_string::join_paths(
			$pdir,
			'src/styles/',
			$folder
		);
		$file_url = az_string::join_paths(
			$purl,
			'src/styles/',
			$folder
		);
		if (!\file_exists($file_path)) return;

		if (empty($version)) {
			$version = az_wp::get_plugin_version($plugin_file);
		}

		$plugin_name = az_wp::getPluginName($plugin_file);
		$style_prefix = "$plugin_name-$folder";


		if ($use_cahce) {
			/* -------------------------------------------------------------------------- */
			/*                                  use cache                                 */
			/* -------------------------------------------------------------------------- */
			$cache_folder = az_string::join_paths($pdir, 'cache');
			$cache_folder_url = az_string::join_paths(az_wp::getPluginUrl($plugin_file), 'cache');

			if (!file_exists($cache_folder)) {
				mkdir($cache_folder, 0777, true);
			}
			$compressed_name = $style_prefix . '_' . $version . '.css';
			$compressed_file = az_string::join_paths($cache_folder, $compressed_name);
			if (!\file_exists($compressed_file)) {
				$css_files = az_assets::get_files_in($file_path, '.css');
				$compressed_css = '';
				foreach ($css_files as $file) {
					// Load the content of the css file 
					$css_content = file_get_contents($file);
					$compressed_css .= ' ' . $css_content;
				}
				$compressed_css = az_assets::compress_css($compressed_css);
				$myfile = fopen($compressed_file, "w") or die("unable to open file!");
				fwrite($myfile, $compressed_css);
				fclose($myfile);
			}

			wp_enqueue_style(
				$compressed_name,
				az_string::join_url($cache_folder_url, $compressed_name),
				$deps,
				$version
			);
			return $compressed_name;
		} else {
			/* -------------------------------------------------------------------------- */
			/*                           default mode. no cache                           */
			/* -------------------------------------------------------------------------- */
			$css_files = az_assets::get_files_in($file_path, '.css');
			$loaded_styles = [];
			foreach ($css_files as $file) {
				$file_name = basename($file);
				$style_name = "$style_prefix-$file_name";
				$loaded_styles[] = $style_name;
				wp_enqueue_style(
					$style_name,
					az_string::join_url($file_url, $file_name),
					$deps,
					$version
				);
			}
			return $loaded_styles;
		}
	}


	static function load_js_folder(
		string $plugin_file,
		string $folder,
		array $deps = [],
		string|int $version = null
	) {

		$pdir = az_wp::getPluginDir($plugin_file);
		$purl = az_wp::getPluginUrl($plugin_file);
		$file_path = az_string::join_paths(
			$pdir,
			'src/js/',
			$folder
		);
		$file_url = az_string::join_paths(
			$purl,
			'src/js/',
			$folder
		);
		$plugin_name = az_wp::getPluginName($plugin_file);
		$js_prefix = "$plugin_name-js-$folder";


		/* -------------------------------------------------------------------------- */
		/*                           default mode. no cache                           */
		/* -------------------------------------------------------------------------- */
		$css_files = az_assets::get_files_in($file_path, '.js');
		$loaded_files = [];
		$final_deps = array_merge($deps, static::$default_js_deps);
		foreach ($css_files as $file) {
			$file_name = basename($file);
			$js_name = "$js_prefix-$file_name";
			$loaded_files[] = $js_name;
			wp_enqueue_script(
				$js_name,
				az_string::join_url($file_url, $file_name),
				$final_deps,
				$version
			);
		}
		return $loaded_files;
	}

	/**
	 * prepare plugin js files 
	 */
	static function load_plugin_js(string $plugin_file, array $deps = [])
	{
		$version = az_wp::get_plugin_version($plugin_file);
		add_action(
			'wp_enqueue_scripts',
			function () use ($plugin_file, $version, $deps) {
				foreach (static::$front_folder_conditions as $key => $value) {
					if (is_callable($value) && $value()) {
						az_wp::load_js_folder($plugin_file, $key, $deps, $version);
					}
				}
				foreach (static::$shared_folders as   $value) {
					az_wp::load_js_folder($plugin_file, $value, $deps, $version);
				}
			}
		);
		add_action(
			'admin_enqueue_scripts',
			function () use ($plugin_file, $version, $deps) {
				foreach (static::$admin_folders as  $value) {
					az_wp::load_js_folder($plugin_file, $value, $deps, $version);
				}
				foreach (static::$shared_folders as   $value) {
					az_wp::load_js_folder($plugin_file, $value, $deps, $version);
				}
			}
		);
	}
	/**
	 * prepare plugin js files 
	 */
	static function load_plugin_css(string $plugin_file, array $deps = [])
	{
		$version = az_wp::get_plugin_version($plugin_file);

		add_action(
			'wp_enqueue_scripts',
			function () use ($plugin_file, $version, $deps) {
				foreach (static::$front_folder_conditions as $key => $value) {
					if (is_callable($value) && $value()) {
						az_wp::load_style_folder($plugin_file, $key, $deps, $version);
					}
				}
				foreach (static::$shared_folders as   $value) {
					az_wp::load_style_folder($plugin_file, $value, $deps, $version);
				}
			}
		);
		add_action(
			'admin_enqueue_scripts',
			function () use ($plugin_file, $version, $deps) {
				foreach (static::$admin_folders as   $value) {
					az_wp::load_style_folder($plugin_file, $value, $deps, $version);
				}
				foreach (static::$shared_folders as   $value) {
					az_wp::load_style_folder($plugin_file, $value, $deps, $version);
				}
			}
		);
	}
	static function load_plugin(string $plugin_file)
	{
		az_wp::load_plugin_js($plugin_file);
		az_wp::load_plugin_css($plugin_file);
	}
	/**
	 * is cart or checkout page 
	 */
	private static function is_cart_checkout()
	{
		if (
			!function_exists('is_cart')
			|| !function_exists('is_checkout')
		) {
			return false;
		}
		return is_cart() || is_checkout();
	}
}
