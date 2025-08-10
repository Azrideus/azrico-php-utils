<?php

namespace AzUtils\module;

use AzUtils\wp\az_wp_settings;
use AzUtils\az_string;

abstract class az_module extends plugin_object
{
	readonly string $module_name;
	readonly string $module_name_slug;
	readonly string $setting_page_name;
	readonly string $setting_name;
	readonly string $settings_title;
	readonly array $setting_fields;

	public function __construct(string $plugin_name, string $module_name)
	{
		parent::__construct($plugin_name);
		$this->module_name = $module_name;
		$this->settings_title = \ucfirst($plugin_name);
		$this->module_name_slug = $this->plugin_name_slug . '__' . az_string::slugify($this->module_name);
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

	/** 
	 * @return az_setting_field[]
	 */
	public function getSettingFields()
	{
		$sections = $this->getSettingSections();
		$fields = [];
		foreach ($sections as $section) {
			foreach ($section->fields as $f) {
				$fields[] = $f;
			}
		}
		return $fields;
	}
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
		$count = 0;
		foreach ($module_list as $module) {
			if ($module instanceof az_module) {
				$module->init();
				if (az_wp_settings::register_module($module)) {

					$count++;
				}
			}
		}
		return $count;
	}
}
