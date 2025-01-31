<?php

namespace AzUtils\az_i18n;

use AzUtils;

class az_i18n extends AzUtils\AzUtilsCore
{
	static $current_domain = '';
	static $pending_domains = [];
	public static function init(string $plugin_text_domain, string $plugin_root_dir)
	{
		$lang_dir = $plugin_root_dir . '/languages/';

		assert(is_string($plugin_text_domain)
			&& strlen($plugin_text_domain) > 0, "Invalid plugin text domain: $plugin_text_domain");
		assert(is_dir($lang_dir), "Language directory not found: $lang_dir");


		self::$pending_domains[] = [$plugin_text_domain, $lang_dir];
		$callback = array(self::class, 'load_textdomain');
		if (!has_action('init', $callback)) {
			add_action('init', $callback);
		}
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
