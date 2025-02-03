<?php

namespace AzUtils;


abstract class az_assets
{

	public static function get_url(string $file, string $name)
	{
		$root_dir = az_wp::getPluginUrl($file);
		return az_string::join_url($root_dir, 'assets', $name);
	}
}
