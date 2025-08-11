<?php

namespace AzUtils\module;

use AzUtils\wp\az_wp_settings;
use AzUtils\az_string;

abstract class az_module extends plugin_module_object
{
	private static $all_modules = [];
	private static $action_registred = false;

	readonly string $settings_title;
	private array $setting_sections;

	public function __construct(string $plugin_name, string $module_name)
	{
		parent::__construct($plugin_name, $module_name);
		$this->settings_title = \ucfirst($plugin_name);
		$this->setting_sections = [];

		/* ------------------------------ MAIN SECTION ------------------------------ */
		$main_section = new az_setting_section($this, [
			'name' => 'main_section',
			'title' => 'Core Settings',
			'desc' => '',
			'class' => 'az-settings-main-section',
		]);
		$main_section->add_field(new az_setting_field(
			$main_section,
			[
				'name' => $this->module_name_slug . '__enabled',
				'title' => $this->module_name . ' Enabled',
				'label' => $this->module_name . ' Enabled',
				'type' => 'boolean',
			]
		));
		$this->setting_sections[] = $main_section;
	}
	/* -------------------------------------------------------------------------- */

	public abstract function init();

	/** 
	 * @return az_setting_section[]
	 */
	public function add_section(az_setting_section|array $sections_to_add)
	{
		if (\is_array($sections_to_add)) {
			foreach ($sections_to_add as $s)
				$this->add_section($s);
			return $this->get_setting_sections();
		}
		$this->setting_sections[$sections_to_add->name] = $sections_to_add;
		return $this->get_setting_sections();
	}

	/** 
	 * @return az_setting_section[]
	 */
	public function get_setting_sections()
	{
		return $this->setting_sections;
	}
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
		return null;
	}
	/**
	 * Check if the module is enabled. 
	 */
	public function is_enabled()
	{
		return $this->get_plugin_option_boolean($this->module_name_slug . '__enabled', true);
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
