<?php

namespace AzUtils;


abstract class az_assets
{

	public static function get_asset_path(string $file, string $name)
	{
		$root_dir = az_wp::getPluginDir($file);
		return az_string::join_paths($root_dir, 'assets', $name);
	}
}
