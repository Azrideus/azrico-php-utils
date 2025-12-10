<?php

namespace AzUtils;


abstract class az_i18n
{
	private static $enabled = true;
	/**
	 * name of the cache group
	 */
	abstract public static function getDomain();

	/**
	 * a reference to any path in the current plugin
	 * this is needed so we know where to load language files from 
	 */
	abstract public static function getPath();

	public static function set_enabled(bool $en)
	{
		static::$enabled = $en;
	}

	public static function init($debug = false, $priority = 10)
	{
		$plugin_root_dir = az_wp::getPluginDir(static::getPath());

		$domain = static::getDomain();
		$lang_dir = plugin_basename($plugin_root_dir) . '/languages/';
		$lang_dir_full = $plugin_root_dir . '/languages/';
		assert(is_string($domain), "Invalid plugin text domain");
		assert(file_exists($plugin_root_dir), "plugin directory not found: $plugin_root_dir");
		assert(file_exists($lang_dir_full), "Language directory not found: $lang_dir_full");

		add_action(
			'init',
			function () use ($domain, $lang_dir, $debug) {
				load_plugin_textdomain(
					$domain,
					false,
					$lang_dir
				);
			},
			$priority
		);
		if ($debug) {
			add_action('wp_loaded', function () use ($domain, $lang_dir, $debug) {

				if (is_textdomain_loaded($domain)) \error_log($domain . ' domain is loaded');
				else \error_log($domain . ' domain is NOT loaded');

				$data = get_translations_for_domain($domain);
				foreach ($data->entries as $entry) {
					error_log("Original :" . $entry->singular);
					error_log("Translated :" . $entry->translations[0]);
				}
			}, 8000);
		}
	}

	public static function translate(string $str, ...$params)
	{
		if (!static::$enabled) return $str;
		$translated = __($str, static::getDomain());
		if ($translated === $str) {
			/**
			 * maybe translation was saved in lowercase, so we need to check for that too
			 */
			$lc_str = strtolower($str);
			$lc_translated = __($lc_str, static::getDomain());
			if ($lc_str != $lc_translated) $translated = $lc_translated;
		}
		return sprintf($translated, ...$params);
	}
	public static function etranslate(string $str, ...$params)
	{
		echo static::translate($str, ...$params);
	}
	public static function echo_translate(string $str, ...$params)
	{
		echo static::translate($str, ...$params);
	}


	/**
	 * A post can be a project or blog or product etc. 
	 * This function tries to determine the actual type of the post
	 */
	public static function get_actual_post_type(object|string $item)
	{
		if (is_a($item, 'WP_Post')) {
			$ptype = $item->post_type;
			$ptitle = strtolower($item->post_title);
			$pname = strtolower($item->post_name);
		} else {
			$ptype = strval($item);
			$ptitle = strtolower($ptype);
			$pname = strtolower($ptype);
		}

		if (
			$ptype === 'project' ||
			($ptype === 'post'
				&& (str_contains($ptitle, 'proje')
					|| str_contains($ptitle, 'پروژه')))
		) {
			if (
				str_contains($ptitle, 'arduino')
				|| str_contains($ptitle, 'آردوینو')
				|| str_contains($ptitle, 'اردوینو')
			)
				return 'Arduino Project';
			return 'Project';
		}

		if ($ptype === 'external')
			return 'External';
		if ($ptype === 'product')
			return 'Shop';
		if ($ptype === 'blog')
			return 'Blog';
		if ($ptype === 'product_doc')
			return 'Product Wiki';
		if ($ptype === 'atlas')
			return 'Atlas';
		if ($ptype === 'handbook')
			return 'Handbook';

		if ($ptype === 'attachment') {
			if (str_contains($ptitle, 'video'))
				return 'Video';

			if (str_contains($ptitle, 'datasheet') || str_contains($ptitle, 'data-sheet'))
				return 'Datasheet';

			if (str_contains($ptitle, 'schematic'))
				return 'Schematic';

			if (str_contains($pname, 'github'))
				return 'Github';

			if (str_contains($ptitle, 'source') || str_contains($ptitle, 'keil'))
				return 'Source Code';

			if (str_contains($ptitle, 'cube'))
				return 'Cube Project';

			return 'Attachment';
		}
		return $ptype;
	}
	public static function translate_post_type(object $item): string
	{
		return static::translate(static::get_actual_post_type($item));
	}
}
