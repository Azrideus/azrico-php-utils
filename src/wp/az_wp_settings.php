<?php

namespace AzUtils\wp;

use AzUtils\module\az_module;

/**
 * Extend this class to create a settings page for your plugin.
 * Implement the abstract methods to define the settings title, name, and fields.
 */
class az_wp_settings
{
	private static $page_created = false;
	private static $registred_pages = [];


	public static function getSettingPageSlug(): string
	{
		return 'azrico';
	}
	public static function getModule(string|az_module $s): az_module|null
	{
		if ($s instanceof az_module) {
			static::$registred_pages[$s->plugin_name_slug] = $s;
			return $s;
		}
		if (isset(static::$registred_pages[$s])) return static::$registred_pages[$s];
		return null;
	}

	public static function init()
	{
		add_action('admin_menu', [static::class, '__register_settings_page'], 9);
	}
	/**
	 * We create one static settings page for all plugins and modules
	 */
	public static function __register_settings_page()
	{
		if (static::$page_created)
			return;
		static::$page_created = true;
		add_menu_page(
			'AZ Setting',
			'AZ Setting',
			'manage_options',
			static::getSettingPageSlug(),
			[static::class, '__render_base_settings_page'],
			'dashicons-admin-generic'
		);
	}

	/** 
	 * Register a module settings page by adding it under the `az` settings
	 */
	public static function register_module(az_module $module)
	{
		if (isset(static::$registred_pages[$module->plugin_name_slug])) {
			throw new \Exception("Module with slug {$module->plugin_name_slug} is already registered.");
		}
		$module = static::getModule($module);

		/* ---------------------- Add the Module Settings Page ---------------------- */
		add_submenu_page(
			static::getSettingPageSlug(),                		// Parent slug (must match top-level menu slug)
			$module->settings_title,                  			// Page title
			$module->settings_title,                  			// Menu title
			'manage_options',             						// Capability
			$module->plugin_name_slug,         					// Menu slug
			[static::class, '__render_base_settings_page']      // Callback
		);
		register_setting(
			$module->setting_page_name,
			$module->setting_page_name,
			[
				'sanitize_callback' => function ($input) use ($module) {
					return \AzUtils\wp\az_wp_settings::__sanitize_callback($module, (array) $input);
				}
			]
		);
		add_settings_section(
			$module->setting_page_name,
			$module->plugin_name,
			[static::class, '__section_description'],
			$module->plugin_name_slug,
			['desc' => 'Settings for ' . $module->plugin_name]
		);
		/* ---------------------------- Register Sections --------------------------- */
		$other_sections = $module->getSettingSections();
		foreach ($other_sections as $section) {
			$section->register();
			/* ----------------------------- Register Fields ---------------------------- */
			$fields = $section->getSettingFields();
			foreach ($fields as $field) {
				$field->register();
			}
		}
	}


	public static function __render_base_settings_page()
	{
?>
		<div class="wrap">
			<h1><?php echo "This is the landing page for all az based wordpress plugins" ?></h1>
			<h2><?php echo "to access other plugins use the menu and click on the plugin's name" ?></h2>
		</div>
<?php
	}
	public static function __sanitize_callback(az_module $module, array $input)
	{
		$sanitized = [];
		$fields = $module->getSettingFields();
		foreach ($fields as $field) {
			$type = $field['type'];
			$name = $field['name'];
			switch ($type) {
				case 'text':
					$sanitized[$name] = sanitize_text_field($input[$name] ?? '');
					break;
				case 'checkbox':
					$sanitized[$name] = !empty($input[$name]) ? 1 : 0;
					break;
			}
		}

		return $sanitized;
	}
	public static function get_option(string $plugin_name, string $key): mixed
	{
		$options = get_option($plugin_name);
		if (!is_array($options)) return null;
		if (!isset($options[$key])) return null;
		return $options[$key];
		throw new \Exception("Option '$key' not found in settings.");
	}
	public static function get_option_string(string $plugin_name, string $key): bool
	{
		return \strval(static::get_option($plugin_name, $key));
	}
	public static function get_option_boolean(string $plugin_name, string $key): bool
	{
		return \filter_var(static::get_option($plugin_name, $key), FILTER_VALIDATE_BOOLEAN);
	}

	public static function get_public_post_types()
	{
		return get_post_types(['public' => true], 'objects');
	}
	public static function op_name($arr)
	{
		return join('__', $arr);
	}
}
