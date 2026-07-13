<?php

namespace AzUtils\module;

use AzUtils\wp\az_wp_settings;
use AzUtils\az_string;

/**
 * This object is registered to a plugin
 */
class plugin_object
{
	public string $plugin_name;
	public string $plugin_name_slug;
	public string $plugin_settings_slug;

	public function __construct(string $plugin_name)
	{
		$this->plugin_name = $plugin_name;
		$this->plugin_name_slug = az_string::slugify($this->plugin_name);
		$this->plugin_settings_slug = $this->plugin_name_slug;
	}
	public  function get_plugin_option(string $key)
	{
		$options = get_option($this->plugin_settings_slug, []);
		if (isset($options[$key]))
			return $options[$key];

		return null;
	}
	public  function get_plugin_option_string(string $key)
	{
		return az_wp_settings::get_plugin_option_string($this->plugin_name, $key);
	}
	public  function get_plugin_option_boolean(string $key)
	{
		return az_wp_settings::get_plugin_option_boolean($this->plugin_name, $key);
	}
}
