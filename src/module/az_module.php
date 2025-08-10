<?php

namespace AzUtils\module;

use AzUtils\wp\az_wp_settings;

abstract class az_module
{
	readonly string $plugin_name;
	readonly string $plugin_name_slug;
	readonly string $module_name;
	readonly string $setting_page_name;
	readonly string $setting_name;
	readonly string $settings_title;
	readonly array $setting_fields;

	public function __construct(string $plugin_name, string $module_name)
	{
		$this->plugin_name = $plugin_name;
		$this->module_name = $module_name;
		$this->settings_title = \ucfirst($plugin_name);

		$this->plugin_name_slug = az_string::slugify($this->plugin_name);
		$this->setting_page_name = $this->plugin_name_slug . '_settings';
		$this->setting_name = \str_replace(' ', '_', strtolower($this->plugin_name . '_' . $this->module_name));
		$this->setting_fields = [];
	}
	/* -------------------------------------------------------------------------- */

	public abstract function init();
	/** 
	 * @return az_setting_section[]
	 */
	public abstract function getSettingSections();
	public function getSection(string $section_name): az_setting_section
	{
		$sections = $this->getSettingSections();
		foreach ($sections as $section) {
			if ($section->name === $section_name)
				return $section;
		}
		throw new \Exception("Section {$section_name} not found in module {$this->module_name}");
	}

	/* -------------------------------------------------------------------------- */
	/**
	 * Register a list of modules that implement the az_module interface. 
	 */
	public static function register_modules(array $module_list)
	{
		foreach ($module_list as $module) {
			if ($module instanceof az_module) {
				$module->init();
				az_wp_settings::register_module($module);
			}
		}
	}
}
