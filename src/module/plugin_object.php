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
	public  function get_plugin_option(string $key): mixed
	{
		return az_wp_settings::get_plugin_option($this->plugin_name, $key);
	}
	public  function get_plugin_option_string(string $key): bool
	{
		return az_wp_settings::get_plugin_option_string($this->plugin_name, $key);
	}
	public  function get_plugin_option_boolean(string $key): bool
	{
		return az_wp_settings::get_plugin_option_boolean($this->plugin_name, $key);
	}
}
