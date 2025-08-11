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
		$this->field_name = az_wp_settings::op_name([$this->plugin_name_slug, ($data['name'] ?? $data['field_name'])]);

		$this->title = $data['title'] ?? '';
		$this->label = $data['label'] ?? $this->title  ?? '';
		$this->type = $this->getInputType($data['type'] ?? 'text');
		$section->push_field($this);
	}
	public function register()
	{
		if (empty($this->plugin_name) || empty($this->module_name) || empty($this->section_name))
			throw new \Exception("Field data must be set before registration.");
		if ($this->registered)
			throw new \Exception("Field {$this->plugin_name}->{$this->module_name}->{$this->section_name}->{$this->field_name} is already registered.");
		$this->registered = true;

		/* -------------------------- register the setting -------------------------- */
		register_setting(
			$this->module_settings_group_slug,  // Group Name
			$this->field_name,			 		// Option Name (plugin slug)
			[
				'sanitize_callback' => [$this, '__sanitize_callback'],
			]
		);
		/* --------------------------- register the field --------------------------- */
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
		switch ($this->type) {
			case 'text':
				return $this->get_plugin_option_string($this->field_name);
			case 'checkbox':
				return $this->get_plugin_option_boolean($this->field_name);
		}
		return  $this->get_plugin_option($this->field_name);
	}

	public function __sanitize_callback($input)
	{
		switch ($this->type) {
			case 'text':
				return sanitize_text_field($input ?? '');
			case 'checkbox':
				return empty($input) ?  0 : 1;
		}
		return null;
	}
	public function render_setting_field()
	{
		switch ($this->type) {
			case 'text':
				az_view::esc_attr_printf(
					'<input type="text" name="%s" data-section="%s" value="%s" class="regular-text">',
					($this->field_name),
					($this->section_name),
					($this->getValue()),
				);
				break;
			case 'checkbox':
				az_view::esc_attr_printf(
					'<input type="hidden" name="%s" value="0">',
					($this->field_name),
				);
				az_view::esc_attr_printf(
					'<label><input type="checkbox" name="%s" data-section="%s" value="1" %s> Enable %s</label>',
					($this->field_name),
					($this->section_name),
					checked($this->getValue(), true, false),
					($this->label)
				);
				break;
		}
	}

	public function getInputType($from)
	{
		switch ($from) {
			case 'text':
			case 'string':
				return 'text';
			case 'boolean':
			case 'checkbox':
				return 'checkbox';
		}
		return $from; // Default to the type defined
	}
}
