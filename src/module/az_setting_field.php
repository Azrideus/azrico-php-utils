<?php

namespace AzUtils\module;

use AzUtils\az_view;
use AzUtils\wp\az_wp_settings;

class az_setting_field extends plugin_module_object
{
	readonly string $section_name;

	readonly string $field_name;
	readonly string $title;
	readonly string $label;
	readonly string $type;
	private bool $registered = false;

	public function __construct(
		az_setting_section $section,
		array $data
	) {
		parent::__construct($section->plugin_name, $section->module_name);

		$this->section_name = $section->name;

		$this->field_name = $data['name'] ?? $data['field_name'];
		$this->title = $data['title'] ?? '';
		$this->label = $data['label'] ?? $this->title  ?? '';
		$this->type = $data['type'] ?? 'text';
	}
	public function register()
	{
		if (empty($this->plugin_name) || empty($this->module_name) || empty($this->section_name))
			throw new \Exception("Field data must be set before registration.");
		if ($this->registered)
			throw new \Exception("Field {$this->plugin_name}->{$this->module_name}->{$this->section_name}->{$this->field_name} is already registered.");


		$this->registered = true;
		add_settings_field(
			$this->field_name,
			$this->title,
			[$this, 'render_setting_field'],
			$this->module_settings_page_slug,
			$this->section_name,
			['field' => $this]
		);
	}
	public function getValue()
	{
		return az_wp_settings::get_plugin_option($this->plugin_name, $this->field_name);
	}
	public function render_setting_field()
	{
		switch ($this->type) {
			case 'text':
				az_view::esc_attr_printf(
					'<input type="text" name="%1$s[%2$s]" value="%3$s" data-section="%4$s" class="regular-text">',
					($this->plugin_name),
					($this->field_name),
					($this->getValue()),
					($this->section_name),
					($this->label)
				);
				break;
			case 'checkbox':
				az_view::esc_attr_printf(
					'<label><input type="checkbox" name="%1$s[%2$s]" data-section="%4$s" value="1" %3$s> Enable %2$s</label>',
					($this->plugin_name),
					($this->field_name),
					checked($this->getValue(), 1, false),
					($this->section_name),
					($this->label)
				);
				break;
		}
	}
}
