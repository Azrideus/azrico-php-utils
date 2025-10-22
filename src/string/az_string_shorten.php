<?php

namespace AzUtils\string;

trait az_string_shorten
{
	/**
	 * Tries to smartly shorten the string
	 * 
	 * removes anything at the end that is inside parantheses or after a dash
	 */
	static function smart_shorten($str)
	{
		$patterns = [
			'/\s*[\(\[\{].*[\)\]\}]\s*$/', // remove anything in parantheses at the end
			'/\s*[-–—]\s*[^-–—]*$/', // remove anything after a dash at the end
		];
		foreach ($patterns as $pattern) {
			$shortened = preg_replace($pattern, '', $str);
			if (strlen($shortened) < strlen($str)) {
				$str = $shortened;
			}
		}
		return $str;
	}
	/**
	 * get the short name of a post by comparing its title and slug 
	 * 
	 * it also removes some predefined prefixes from the title
	 */
	public static function get_post_shortname(array|object $input): string
	{

		if (is_a($input, 'WC_Product')) {
			$title = $input->get_title();
			$slug = $input->get_slug();
		} else if (is_object($input)) {
			$title = $input->post_title;
			$slug = $input->post_name;
		} else if (is_array($input)) {
			$title = $input['post_title'];
			$slug = $input['post_name'];
		}
		if (!empty($title) && !empty($slug)) {
			$title = self::find_upto($title, $slug);;
		}

		$extra_prefixes = ['بررسی اجمالی ماژول', 'بررسی اجمالی', 'مروری بر', 'بررسی', "Overview of"];
		foreach ($extra_prefixes as $prefix) {
			if (\str_contains($title, $prefix)) {
				$title_parts = explode($prefix, $title);
				$title = $title_parts[1] ?? $title;
			}
		}
		return self::trim_post_name($title);
	}

	public static function trim_post_name(string $title): string
	{
		return trim(trim($title, '*_- '));
	}
}
