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
		$css_files = scandir($file_path);

		foreach ($css_files as $file) {
			if (!\str_ends_with($file, '.css')) continue;
			wp_enqueue_style(
				'azpcb-' . $file,
				az_string::join_url($url_path, $file),
				$deps,
				$version
			);
		}
	}
}
