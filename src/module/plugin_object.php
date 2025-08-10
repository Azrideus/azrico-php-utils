<?php

namespace AzUtils\module;

use AzUtils\wp\az_wp_settings;
use AzUtils\az_string;

/**
 * This object is registered to a plugin
 */
class plugin_object
{
	readonly string $plugin_name;
	readonly string $plugin_name_slug;

	public function __construct(string $plugin_name)
	{
		$this->plugin_name = $plugin_name;
		$this->plugin_name_slug = az_string::slugify($this->plugin_name);
	}
}
