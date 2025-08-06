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
	abstract public static function getSettingSections();

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
	public static function getMainSectionName()
	{
		return static::getSettingsName() . '_section';
	}

	public static function init()
	{
		add_action('admin_menu', [static::class, '__register_settings_page']);
		add_action('admin_init', [static::class, '__register_settings']);
	}
	public static function __register_settings_page()
	{
		add_menu_page(
			'Gebra Setting',
			'Gebra Setting',
			'manage_options',
			static::getSettingsSlug(),
			[static::class, '__render_settings_page'],
			'dashicons-admin-generic'
		);
	}
	public static function __register_settings_sections()
	{
		$slug = static::getSettingsSlug();
		$main_section = static::getMainSectionName();
		add_settings_section(
			$main_section,
			'Main Settings',
			[static::class, '__section_description'],
			$slug,
			['desc' => 'Main settings for the plugin']
		);
		$other_sections = static::getSettingSections();
		foreach ($other_sections as $section) {
			$class = $section['class'] ?? '';
			$defaults = [
				'name' => '',
				'title' => '',
				'desc' => '',
				'before_section' => "<div class='az-settings-section $class'>",
				'after_section'  => '</div>',
				'section_class'  => '',
			];
			$section = wp_parse_args($section, $defaults);
			add_settings_section(
				$section['name'],
				$section['title'],
				[static::class, '__section_description'],
				$slug,
				$section
			);
		}
	}
	public static function __register_settings()
	{
		$slug = static::getSettingsSlug();
		$main_section = static::getMainSectionName();

		register_setting(
			static::getOptionGroup(),
			static::getOptionName(),
			[static::class, '__sanitize_callback']
		);
		static::__register_settings_sections();

		$fields = static::getSettingFields();
		foreach ($fields as $field) {
			$field_section = $field['section'] ?? $main_section;
			add_settings_field(
				$field['name'],
				$field['title'],
				[static::class, '__render_setting_field'],
				$slug,
				$field_section,
				['field' => $field]
			);
		}
	}
	public static function __section_description($section)
	{
		echo '<p>' . $section['desc'] . '</p>';
	}
	public static function __render_settings_page()
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
	public static function __render_setting_field(array $args)
	{
		$op_name = static::getOptionName();

		$field   = $args['field'];
		$type    = $field['type'];
		$label    = $field['label'] ?? $field['title'] ?? '';
		$field_name    = $field['name'];
		$section    = $field['section'] ?? static::getMainSectionName();

		$value   = static::get_option($field_name);

		switch ($type) {
			case 'text':
				printf(
					'<input type="text" name="%1$s[%2$s]" value="%3$s" data-section="%4$s" class="regular-text">',
					esc_attr($op_name),
					esc_attr($field_name),
					esc_attr($value),
					esc_attr($section)
				);
				break;
			case 'checkbox':
				printf(
					'<label><input type="checkbox" name="%1$s[%2$s]" data-section="%4$s" value="1" %3$s> Enable %2$s</label>',
					esc_attr($op_name),
					esc_attr($field_name),
					checked($value, 1, false),
					esc_attr($section),
					esc_attr($label)
				);
				break;
		}
	}
	public static function __sanitize_callback(array $input)
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
	public static function get_option(string $key): mixed
	{
		$options = get_option(static::getOptionName());
		if (!is_array($options)) return null;
		if (!isset($options[$key])) return null;
		return $options[$key];
		throw new \Exception("Option '$key' not found in settings.");
	}
	public static function get_option_boolean(string $key): bool
	{
		return \filter_var(static::get_option($key), FILTER_VALIDATE_BOOLEAN);
	}
}
