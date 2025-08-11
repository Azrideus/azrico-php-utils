<?php

namespace AzUtils\wp;

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
				'args' => ['plugin_name' => $module->plugin_settings_slug]
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


		/* ------------------------------ MAIN SECTION ------------------------------ */
		$main_section = new az_setting_section($module, [
			'name' => 'main_section',
			'title' => 'Core Settings',
			'desc' => '',
			'class' => 'az-settings-main-section',
		]);
		$main_section->push_field(new az_setting_field(
			$main_section,
			[
				'name' => $module->module_name_slug . '__enabled',
				'title' => $module->module_name . ' Enabled',
				'label' => $module->module_name . ' Enabled',
				'type' => 'boolean',
			]
		));
		/* ---------------------------- Register Sections --------------------------- */
		$other_sections = $module->get_setting_sections() ?? [];
		$all_sections = [$main_section, ...$other_sections];
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

	public function __sanitize_callback($input, $option)
	{
		if (!is_array($input))
			$input = [];
		$plugin_name = $option->args['plugin_name'] ?? '';
		if (!$plugin_name) {
			error_log("No plugin name provided for sanitization");
			return $input;
		}
		return null;
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
				settings_fields($module->module_settings_group_slug); 	// GROUP for this page
				do_settings_sections($module->module_settings_page_slug);  // PAGE slug for this page
				submit_button();
				?>
			</form>
		</div>
<?php
	}

	public static function get_plugin_option(string $plugin_name, string $key): mixed
	{
		return get_option(static::op_name([$plugin_name, $key]));
	}
	public static function get_plugin_option_string(string $plugin_name, string $key)
	{
		return \strval(static::get_plugin_option($plugin_name, $key));
	}
	public static function get_plugin_option_boolean(string $plugin_name, string $key)
	{
		return \filter_var(static::get_plugin_option($plugin_name, $key), FILTER_VALIDATE_BOOLEAN);
	}

	public static function op_name($arr)
	{
		foreach ($arr as $key => $value) {
			$arr[$key] = az_string::slugify($value);
		}
		return join('__', $arr);
	}
}
