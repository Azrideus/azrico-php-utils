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
		$url_path = az_string::join_url(
			az_wp::getPluginUrl($plugin_file),
			'src/styles/',
			$folder
		);
		$file_path = az_string::join_paths(
			az_wp::getPluginDir($plugin_file),
			'src/styles/',
			$folder
		);
		$plugin_name = az_wp::getPluginName($plugin_file);

		$css_files = scandir($file_path);

		foreach ($css_files as $file) {
			if (!\str_ends_with($file, '.css')) continue;
			$style_name = 'az_style__' . $plugin_name . '__' . $file;
			wp_enqueue_style(
				$style_name,
				az_string::join_url($url_path, $file),
				$deps,
				$version
			);
		}
	}
}
