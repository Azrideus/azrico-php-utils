<?php

namespace AzUtils;

use AzUtils\az_wp;

abstract class az_assets
{

	public static function get_url(string $file, string $name)
	{
		return az_wp::getPluginUrl($file, '/assets/' . $name);
	}
}
