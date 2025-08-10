<?php

namespace AzUtils\module;


class az_setting_section extends plugin_module_object
{
	readonly string $name;

	readonly string $title;
	readonly string $desc;
	readonly string $class;
	readonly string $before_section;
	readonly string $after_section;
	public array $fields;
	private bool $registered = false;


	public function __construct(az_module $module, array $data, array $fields = [])
	{
		parent::__construct($module->plugin_name, $module->module_name);

		$this->name = $data['name'] ?? $data['section_name'] ?? '';
		$this->title = $data['title'] ?? '';
		$this->desc = $data['desc'] ?? '';
		$this->class = $data['class'] ?? '';

		$this->fields = $fields;

		$this->before_section = "<div class='az-settings-section {$this->class}'>";
		$this->after_section = '</div>';
	}
	public function getSettingFields()
	{
		return $this->fields;
	}
	public function register()
	{
		if (empty($this->plugin_name) || empty($this->module_name) || empty($this->name))
			throw new \Exception("Section data must be set before registration.");
		if ($this->registered)
			throw new \Exception("Section {$this->plugin_name}->{$this->module_name}->{$this->name} is already registered.");

		add_settings_section(
			$this->name,
			$this->title,
			[$this, 'section_description'],
			$this->module_settings_page_slug,
			[
				'class' => $this->class,
				'before_section' => $this->before_section,
				'after_section' => $this->after_section
			]
		);
	}
	public function section_description()
	{
		echo '<p>' . $this->desc . '</p>';
	}
}
