<?php

namespace AzUtils\wp;


abstract class az_wp_settings
{
	abstract public static function getSettingsTitle();
	abstract public static function getSettingsName();
	abstract public static function getSettingFields();

	public static function getSettingsSlug()
	{
		return self::getSettingsName() . '_settings';
	}
	public static function getOptionGroup()
	{
		return self::getSettingsName() . '_options_group';
	}
	public static function getOptionName()
	{
		return self::getSettingsName() . '_settings';
	}
	public static function getSectionName()
	{
		return self::getSettingsName() . '_section';
	}

	public static function init()
	{
		add_action('admin_menu', [self::class, 'register_settings_page']);
		add_action('admin_init', [self::class, 'register_settings']);
	}
	public static function register_settings_page()
	{
		add_menu_page(
			'Gebra Setting',
			'Gebra Setting',
			'manage_options',
			self::getSettingsSlug(),
			[static::class, 'render_settings_page'],
			'dashicons-admin-generic'
		);
	}
	public static function register_settings()
	{
		$slug = self::getSettingsSlug();
		$section = self::getSectionName();

		register_setting(
			self::getOptionGroup(),
			self::getOptionName(),
			[self::class, 'sanitize_callback']
		);

		add_settings_section(
			$section,
			'Main Settings',
			[self::class, 'section_description'],
			$slug
		);

		$fields = self::getSettingFields();
		foreach ($fields as $field) {
			add_settings_field(
				$field,
				$field['label'],
				[self::class, 'render_setting_field'],
				$slug,
				$section,
				['field' => $field]
			);
		}
	}
	public static function section_description()
	{
		echo '<p>Configure the main settings for the plugin below.</p>';
	}
	public static function render_settings_page()
	{
?>
		<div class="wrap">
			<h1><?php echo self::getSettingsTitle() ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields(self::getOptionGroup());
				do_settings_sections(self::getSettingsSlug());
				submit_button();
				?>
			</form>
		</div>
<?php
	}
	public static function render_setting_field($args)
	{
		$op_name = self::getOptionName();

		$field   = $args['field'];
		$type    = $field['type'];
		$field_name    = $field['name'];

		$options = get_option($op_name);
		$value   = $options[$field] ?? '';

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
	public static function sanitize_callback($input)
	{
		$sanitized = [];
		$fields = self::getSettingFields();
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
}
