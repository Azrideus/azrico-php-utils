<?php

namespace AzUtils\module;

use AzUtils\wp\az_wp_settings;
use AzUtils\az_string;

abstract class az_module extends plugin_module_object
{
	private static $all_modules = [];
	private static $action_registred = false;

	readonly string $settings_title;
	readonly array $setting_fields;

	public function __construct(string $plugin_name, string $module_name)
	{
		parent::__construct($plugin_name, $module_name);
		$this->settings_title = \ucfirst($plugin_name);
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
			foreach ($section->get_fields() as $f) {
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
		foreach ($module_list as $module)
			if ($module instanceof az_module)
				static::$all_modules[$module->module_name_slug] = $module;

		if (!static::$action_registred) {
			if (\did_action('init')) {
				static::__init_modules();
			} else {
				add_action('init', [static::class, '__init_modules'], 999);
			}
			static::$action_registred = true;
		}
	}
	public static function __init_modules()
	{
		add_action('admin_menu', [static::class, '__init_settings']);
		add_action('admin_init', [static::class, '__init_setting_fields']);
		foreach (static::$all_modules as $module)
			$module->init();
	}
	public static function __init_settings()
	{
		foreach (static::$all_modules as $module)
			az_wp_settings::register_module_setting_pages($module);
	}
	public static function __init_setting_fields()
	{
		foreach (static::$all_modules as $module)
			az_wp_settings::register_module_setting_fields($module);
	}
}
