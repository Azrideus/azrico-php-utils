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
		assert(file_exists($lang_dir), "Language directory not found: $lang_dir");


		static::$pending_domains[] = [$plugin_text_domain, $lang_dir];

		$action
			= array(static::class, 'load_textdomain');
		if (!has_action('init', $action))
			add_action('init', $action);
	}
	public static function load_textdomain()
	{
		foreach (static::$pending_domains as $pln) {
			$name = $pln[0];
			$path = $pln[1];
			load_plugin_textdomain(
				$name,
				false,
				$path
			);
		}
		static::$pending_domains = [];
	}

	public static function translate(string $str, ...$params)
	{
		return sprintf(__($str, static::$current_domain), ...$params);
	}
	public static function etranslate(string $str)
	{
		echo static::translate($str);
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
