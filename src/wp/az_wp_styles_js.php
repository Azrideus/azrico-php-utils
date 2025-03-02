<?php

namespace AzUtils\wp;

use AzUtils\az_string;
use AzUtils\az_wp;

trait az_wp_styles_js
{
	/**
	 * get a list of all css  files in given dir 
	 */
	private static function get_style_files($file_path)
	{
		$css_files = scandir($file_path);
		$css_files = array_filter($css_files, function ($file) {
			return \str_ends_with($file, '.css');
		});
		return $css_files;
	}
	private static function get_js_files($file_path)
	{
		$css_files = scandir($file_path);
		$css_files = array_filter($css_files, function ($file) {
			return \str_ends_with($file, '.js');
		});
		return $css_files;
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
				$css_files = self::get_style_files($file_path);
				$compressed_css = '';
				foreach ($css_files as $file) {
					// Load the content of the css file 
					$css_content = file_get_contents(az_string::join_paths($file_path, $file));
					$compressed_css .= ' ' . $css_content;
				}
				$compressed_css = self::compress_css($compressed_css);
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
			$css_files = self::get_style_files($file_path);
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
	static function compress_css($input)
	{
		// Remove space after colons
		$input = str_replace(': ', ':', $input);


		$input = str_replace(["\r", "\n", "\t"], '', $input);

		// input whitespace
		$input = str_replace(
			array(
				'  ',
				'    ',
				'    '
			),
			'',
			$input
		);


		return $input;
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
		$css_files = self::get_js_files($file_path);
		$loaded_files = [];
		foreach ($css_files as $file) {
			$file_name = basename($file);
			$js_name = "$js_prefix-$file_name";
			$loaded_files[] = $js_name;
			wp_enqueue_script(
				$js_name,
				az_string::join_url($file_url, $file_name),
				$deps,
				$version
			);
		}
		return $loaded_files;
	}
}
