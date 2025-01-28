<?php
class az_i18n
{
	static $pending_domains = [];
	public static function init(string $plugin_text_domain, string $plugin_root_dir)
	{

		$lang_dir = $plugin_root_dir . '/languages/';
		assert(is_string($plugin_text_domain) && strlen($plugin_text_domain) > 0, "Invalid plugin text domain: $name");
		assert(is_dir($lang_dir), "Language directory not found: $lang_dir");


		self::$pending_domains[] = [$plugin_text_domain, $lang_dir];
		$callback = array(self::class, 'azutils_load_plugin_textdomain');
		if (!has_action('init', $callback)) {
			add_action('init', $callback);
		}
	}
	public static function azpcb_load_plugin_textdomain()
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
		return sprintf(__($str, 'az-pcb'), ...$params);
	}
	public static function etranslate(string $str)
	{
		echo self::translate($str);
	}
}
