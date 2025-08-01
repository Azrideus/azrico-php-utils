<?php

namespace AzUtils\wp;


/**
 * Extend this class to create a settings page for your plugin.
 * Implement the abstract methods to define the settings title, name, and fields.
 */
abstract class az_wp_settings
{
	abstract public static function getSettingsTitle();
	abstract public static function getSettingsName();
	abstract public static function getSettingFields();

	public static function getSettingsSlug()
	{
		return static::getSettingsName() . '_settings';
	}
	public static function getOptionGroup()
	{
		return static::getSettingsName() . '_options_group';
	}
	public static function getOptionName()
	{
		return static::getSettingsName() . '_settings';
	}
	public static function getSectionName()
	{
		return static::getSettingsName() . '_section';
	}

	public static function init()
	{
		add_action('admin_menu', [static::class, 'register_settings_page']);
		add_action('admin_init', [static::class, 'register_settings']);
	}
	protected static function register_settings_page()
	{
		add_menu_page(
			'Gebra Setting',
			'Gebra Setting',
			'manage_options',
			static::getSettingsSlug(),
			[static::class, 'render_settings_page'],
			'dashicons-admin-generic'
		);
	}
	protected static function register_settings()
	{
		$slug = static::getSettingsSlug();
		$section = static::getSectionName();

		register_setting(
			static::getOptionGroup(),
			static::getOptionName(),
			[static::class, 'sanitize_callback']
		);

		add_settings_section(
			$section,
			'Main Settings',
			[static::class, 'section_description'],
			$slug
		);

		$fields = static::getSettingFields();
		foreach ($fields as $field) {
			add_settings_field(
				$field['name'],
				$field['label'],
				[static::class, 'render_setting_field'],
				$slug,
				$section,
				['field' => $field]
			);
		}
	}
	protected static function section_description()
	{
		echo '<p>Configure the main settings for the plugin below.</p>';
	}
	public static function render_settings_page()
	{
?>
		<div class="wrap">
			<h1><?php echo static::getSettingsTitle() ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields(static::getOptionGroup());
				do_settings_sections(static::getSettingsSlug());
				submit_button();
				?>
			</form>
		</div>
<?php
	}
	public static function render_setting_field($args)
	{
		$op_name = static::getOptionName();

		$field   = $args['field'];
		$type    = $field['type'];
		$field_name    = $field['name'];

		$value   = static::get_option($field_name);

		switch ($type) {
			case 'text':
				printf(
					'<input type="text" name="%1$s[%2$s]" value="%3$s" class="regular-text">',
					esc_attr($op_name),
					esc_attr($field_name),
					esc_attr($value)
				);
				break;
			case 'checkbox':
				printf(
					'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> Enable %2$s</label>',
					esc_attr($op_name),
					esc_attr($field_name),
					checked($value, 1, false)
				);
				break;
		}
	}
	protected static function sanitize_callback($input)
	{
		$sanitized = [];
		$fields = static::getSettingFields();
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
	public static function get_option($key)
	{
		$fields = static::getSettingFields();
		foreach ($fields as $field) {
			if ($field['name'] === $key) {
				$options = get_option(static::getOptionName());
				return $options[$key] ?? null;
			}
		}
		throw new \Exception("Option '$key' not found in settings.");
	}
}
