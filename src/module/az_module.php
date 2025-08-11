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
	public abstract function get_setting_sections();

	/** 
	 * @return az_setting_field[]
	 */
	public function get_setting_fields()
	{
		$sections = $this->get_setting_sections();
		$fields = [];

		if (empty($sections) || !\is_array($sections))
			return $fields; // No sections, no fields 

		foreach ($sections as $section) {
			foreach ($section->get_fields() as $f) {
				$fields[$f->field_name] = $f;
			}
		}
		return $fields;
	}
	public function get_section(string $section_name): az_setting_section|null
	{
		$sections = $this->get_setting_sections();
		if (empty($sections) || !\is_array($sections))
			return null;
		foreach ($sections as $section) {
			if ($section->name === $section_name)
				return $section;
		}
		return null; // Section not found
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
		add_action('admin_menu', [static::class, '__init_settings'], 100);
		add_action('admin_init', [static::class, '__init_setting_fields'], 100);
		foreach (static::$all_modules as $module)
			$module->init();
	}
	public static function __init_settings()
	{
		foreach (static::$all_modules as $module)
			az_wp_settings::register_module_settings($module);
	}
	public static function __init_setting_fields()
	{
		foreach (static::$all_modules as $module)
			az_wp_settings::register_module_setting_fields($module);
	}
}
