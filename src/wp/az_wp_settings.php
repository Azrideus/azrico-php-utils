<?php

namespace AzUtils\wp;

use AzUtils\az_object;
use AzUtils\az_string;
use AzUtils\module\az_module;
use AzUtils\module\az_setting_section;
use AzUtils\module\az_setting_field;

/**
 * Extend this class to create a settings page for your plugin.
 * Implement the abstract methods to define the settings title, name, and fields.
 */
class az_wp_settings
{
	private static $page_created = false;
	private static $registred_modules = [];
	private static $registred_plugins = [];


	public static function getSettingPageSlug(): string
	{
		return 'azrico';
	}
	public static function getModule(string|az_module $s): az_module|null
	{
		if ($s instanceof az_module) {
			static::$registred_modules[$s->module_name_slug] = $s;
			return $s;
		}
		if (isset(static::$registred_modules[$s])) return static::$registred_modules[$s];
		return null;
	}


	/**
	 * We create one static settings page for all plugins and modules
	 */
	private static function register_main_settings_page()
	{
		if (static::$page_created)
			return false;
		static::$page_created = true;
		add_menu_page(
			'GMS Toolkit',
			'GMS Toolkit',
			'manage_options',
			static::getSettingPageSlug(),
			[static::class, '__render_base_settings_page'],
			'dashicons-admin-generic'
		);
		return true;
	}

	public static function register_plugin_settings(az_module $module)
	{
		$plugin_name = $module->plugin_name_slug;
		if (isset(static::$registred_plugins[$plugin_name]))
			return false; // Plugin already registered
		static::$registred_plugins[$plugin_name] = $plugin_name;

		/* -------------------------- register the setting -------------------------- */
		register_setting(
			$module->plugin_settings_slug,  // Group Name
			$module->plugin_settings_slug,	// Option Name (plugin slug)
			[
				'sanitize_callback' => [static::class, '__sanitize_callback'],
			]
		);
		return true;
	}
	/** 
	 * Register a module settings page by adding it under the `az` settings
	 * Each Module has its own settings page under the main `az` settings page.
	 */
	public static function register_module_settings(az_module $module)
	{
		$module = static::getModule($module);
		static::register_main_settings_page();
		static::register_plugin_settings($module);


		/* ---------------------- Add the Module Settings Page ---------------------- */
		add_submenu_page(
			static::getSettingPageSlug(),       // Parent slug (must match top-level menu slug)
			$module->module_name,               // Page title
			$module->module_name,               // Menu title
			'manage_options',             		// Capability
			$module->module_settings_page_slug, // Menu slug
			function () use ($module) {
				return az_wp_settings::__render_settings_page($module);
			},
		);
		return true;
	}
	public static function register_module_setting_fields(az_module $module)
	{
		$count = 0;
		$module = static::getModule($module);



		/* ---------------------------- Register Sections --------------------------- */
		$other_sections = $module->get_setting_sections() ?? [];
		$all_sections = [...$other_sections];
		foreach ($all_sections as $section) {
			$section->register();
			/* ----------------------------- Register Fields ---------------------------- */
			$fields = $section->get_fields();
			foreach ($fields as $field) {
				$field->register();
			}
			$count++;
		}
		return $count;
	}

	public static function __sanitize_callback($input)
	{
		if (!is_array($input))
			$input = [];

		$output = [];
		$all_modules = static::$registred_modules;
		foreach ($all_modules as $m) {
			$fields = $m->get_setting_fields();
			foreach ($fields as $key => $f) {
				if (!isset($input[$key])) {
					/* -------------- Value is not changed, use the existing value -------------- */
					$output[$key] = $m->get_plugin_option($key);
				} else {
					$output[$key] = $f->sanitize($input[$key] ?? null);
				}
			}
		}
		return $output;
	}
	public static function __module_page_description($args)
	{
		$module = $args['module'] ?? null;
		if (!$module) return;
?>
		<div class="wrap">
			<h1><?php echo "Settings for " . $module->module_name ?></h1>
		</div>
	<?php
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
	public static function __render_settings_page(az_module $module)
	{
	?>
		<div class="wrap">
			<h1><?php echo "Settings for " . $module->module_name ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields($module->plugin_settings_slug); 	// GROUP for this page
				do_settings_sections($module->module_settings_page_slug);  // PAGE slug for this page
				submit_button();
				?>
			</form>
		</div>
<?php
	}

	public static function get_plugin_option(string $plugin_name, string $key): mixed
	{
		$ops = get_option(az_string::slugify($plugin_name), []);
		if (empty($ops))
			return null; 		// No options set 

		if (isset($ops[$key]))
			return $ops[$key]; 	// Empty string is not a valid option

		$key = az_string::slugify($key);

		if (isset($ops[$key]))
			return $ops[$key]; // Empty string is not a valid option

		return $ops[$key] ?? null;
	}
	public static function get_plugin_option_string(string $plugin_name, string $key)
	{
		return \strval(static::get_plugin_option($plugin_name, $key));
	}
	public static function get_plugin_option_boolean(string $plugin_name, string $key)
	{
		return az_object::is_truthy(static::get_plugin_option($plugin_name, $key));
	}
}
