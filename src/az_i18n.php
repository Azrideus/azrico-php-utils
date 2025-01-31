<?php

namespace AzUtils;


class az_i18n
{
	static $current_domain = '';
	static $pending_domains = [];
	public static function init(string $plugin_text_domain)
	{
		$plugin_root_dir = az_wp::getPluginDir();
		$lang_dir = $plugin_root_dir . '/languages/';

		assert(is_string($plugin_text_domain)
			&& strlen($plugin_text_domain) > 0, "Invalid plugin text domain: $plugin_text_domain");
		assert(is_dir($lang_dir), "Language directory not found: $lang_dir");


		self::$pending_domains[] = [$plugin_text_domain, $lang_dir];
		add_action('init', array(self::class, 'load_textdomain'));
	}
	public static function load_textdomain()
	{
		foreach (self::$pending_domains as $pln) {
			$name = $pln[0];
			$path = $pln[1];
			load_plugin_textdomain(
				$name,
				false,
				$path
			);
		}
		self::$pending_domains = [];
	}

	public static function translate(string $str, ...$params)
	{
		return sprintf(__($str, static::$current_domain), ...$params);
	}
	public static function etranslate(string $str)
	{
		echo static::translate($str);
	}
}
