<?php

namespace AzUtils;

use AzUtils\az_wp;

class az_assets
{
	static function compress_css($input)
	{
		// Remove space after colons
		$input = str_replace(': ', ':', $input);


		$input = str_replace(["\r", "\n", "\t"], ' ', $input);
		// input whitespace
		$input = str_replace(
			array(
				'  ', // double spaces 
			),
			' ',
			$input
		);
		$input = str_replace(
			array(
				'; ',
				' ;',
			),
			';',
			$input
		);
		$input = str_replace(
			array(
				': ',
				' :',
			),
			':',
			$input
		);
		$input = str_replace(
			array(
				'! ',
				' !',
			),
			'!',
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
		$file_list = \array_map(function ($file) use ($folder) {
			return az_string::join_paths($folder, $file);
		}, $file_list);
		if (!empty($suffix)) {
			$file_list = array_filter($file_list, function ($file) use ($suffix) {
				return \str_ends_with($file, $suffix);
			});
		}
		return $file_list;
	}
	/**
	 * require all files in a folder 
	 */
	static function require_folder(
		string $current_file,
		string $folder,
		$suffix = '.php',
	) {
		$files = self::get_files_in($folder, $suffix);
		foreach ($files as $file) {
			if (\basename($current_file) == \basename($file)) continue; //avoid loops
			require_once $file;
		}
	}
	public static function get_url(string $current_file, string $name)
	{
		return az_wp::getPluginUrl($current_file, '/src/assets/' . $name);
	}
}
