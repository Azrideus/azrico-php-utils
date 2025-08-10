<?php

namespace AzUtils\module;

use AzUtils\wp\az_wp_settings;
use AzUtils\az_string;

/**
 * This object is registered to a plugin's module
 */
class plugin_module_object extends plugin_object
{
	readonly string $module_name;
	readonly string $module_name_slug;
	readonly string $module_settings_page_slug;
	readonly string $module_settings_group_slug;

	public function __construct(string $plugin_name, string $module_name)
	{
		parent::__construct($plugin_name);
		$this->module_name = $module_name;
		$this->module_name_slug = $this->plugin_name_slug . '__' . az_string::slugify($this->module_name);
		$this->module_settings_page_slug = $this->module_name_slug;
		$this->module_settings_group_slug = $this->module_name_slug;
	}
}
