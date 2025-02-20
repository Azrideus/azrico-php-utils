<?php

namespace AzUtils\wp;

use AzUtils\az_string;
use AzUtils\az_wp;

trait az_wp_styles
{
	static function load_style_folder(
		string $plugin_file,
		string $folder,
		array $deps = [],
		string|int $version = null
	) {
		$pdir = az_wp::getPluginDir($plugin_file);
		$cache_folder = az_string::join_paths($pdir, 'cache');
		$cache_folder_url = az_string::join_paths(az_wp::getPluginUrl($plugin_file), 'cache');


		$file_path = az_string::join_paths(
			$pdir,
			'src/styles/',
			$folder
		);
		$plugin_name = az_wp::getPluginName($plugin_file);
		/* -------------------------------------------------------------------------- */
		if (!file_exists($cache_folder)) {
			mkdir($cache_folder, 0777, true);
		}
		/* -------------------------------------------------------------------------- */
		$compressed_name = '__styles_' . $plugin_name . '_' . $folder . '_' . $version . '.css';
		$compressed_file = az_string::join_paths($cache_folder, $compressed_name);
		if (!\file_exists($compressed_file)) {
			$css_files = scandir($file_path);
			$css_files = array_filter($css_files, function ($file) {
				return \str_ends_with($file, '.css');
			});
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
	}
	static function compress_css($input)
	{

		// Remove space after colons
		$input = str_replace(': ', ':', $input);

		// input whitespace
		$input = str_replace(array(
			"\n",
			"\t",
			'  ',
			'    ',
			'    '
		), '', $input);


		return $input;
	}
}
