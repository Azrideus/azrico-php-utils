<?php

namespace AzUtils;

use AzUtils\az_wp;

abstract class az_assets
{
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
	static function get_files_in(
		string $folder,
		$suffix = '',
	) {
		if (!\file_exists($folder)) return [];
		$file_list = scandir($folder);
		if (!empty($suffix)) {
			$file_list = array_filter($file_list, function ($file) use ($suffix) {
				return \str_ends_with($file, $suffix);
			});
		}
		return $file_list;
	}
	public static function get_url(string $file, string $name)
	{
		return az_wp::getPluginUrl($file, '/src/assets/' . $name);
	}
}
