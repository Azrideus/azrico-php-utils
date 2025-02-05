<?php

namespace AzUtils;


abstract class az_i18n
{
	/**
	 * name of the cache group
	 */
	abstract public static function getDomain();

	/**
	 * a reference to any path in the current plugin
	 * this is needed so we know where to load language files from 
	 */
	abstract public static function getPath();

	public static function init($debug = false)
	{
		$plugin_root_dir = az_wp::getPluginDir(self::getPath());
		$lang_dir = $plugin_root_dir . '/languages/';


		$domain = static::getDomain();
		assert(is_string($domain), "Invalid plugin text domain");
		assert(file_exists($lang_dir), "Language directory not found: $lang_dir");


		add_action(
			'init',
			function () use ($domain, $lang_dir, $debug) {
				load_plugin_textdomain(
					$domain,
					false,
					$lang_dir
				);
			},
			2000
		);
		add_action('wp_loaded', function () use ($domain, $lang_dir, $debug) {
			if ($debug) {
				if (is_textdomain_loaded($domain)) \error_log($domain . ' domain is loaded');
				else \error_log($domain . ' domain is NOT loaded');

				$data = get_translations_for_domain($domain);
				foreach ($data->entries as $entry) {
					error_log("Original :" . $entry->singular);
					error_log("Translated :" . $entry->translations[0]);
				}
			}
		}, 8000);
	}

	public static function translate(string $str, ...$params)
	{
		return sprintf(__($str, static::getDomain()), ...$params);
	}
	public static function etranslate(string $str, ...$params)
	{
		echo static::translate($str, ...$params);
	}


	private static function getPostType(object $item)
	{
		assert(is_a($item, 'WP_Post'), 'Invalid post object');
		$ptype = $item->post_type;
		$ptitle = strtolower($item->post_title);
		$pname = strtolower($item->post_name);


		if (
			$ptype === 'project' ||
			($ptype === 'post'
				&& (str_contains($ptitle, 'proje')
					|| str_contains($ptitle, 'پروژه')))
		) {
			return 'project';
		}

		if ($ptype === 'product') {
			return 'shop';
		}

		if ($ptype === 'blog') {
			return 'blog';
		}

		if ($ptype === 'product_doc') {
			return 'Product Wiki';
		}
		if ($ptype === 'attachment') {
			if (str_contains($ptitle, 'video'))
				return 'Video';

			if (str_contains($ptitle, 'datasheet') || str_contains($ptitle, 'data-sheet'))
				return 'Datasheet';

			if (str_contains($ptitle, 'schematic'))
				return 'Schematic';

			if (str_contains($pname, 'github'))
				return 'github';

			if (str_contains($ptitle, 'source') || str_contains($ptitle, 'keil'))
				return 'Source Code';

			if (str_contains($ptitle, 'cube'))
				return 'Cube Project';

			return 'attachment';
		}
		return $item->post_type;
	}
	public static function translatePostType(object $item): string
	{
		return static::translate(static::getPostType($item));
	}
}
