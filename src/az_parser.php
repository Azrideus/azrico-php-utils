<?php

namespace AzUtils;


class az_parser
{
	static $all_headers = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

	/**
	 * find the first header in the post content
	 * and return all content of the those headers
	 *
	 * @param WP_Post $post
	 * @return array
	 */
	static function getHeaders(\WP_Post $post): array
	{

		$doc = new \DOMDocument();
		$html = '<meta charset="utf8">' . $post->post_content; // ;

		if (empty($html)) return [];

		$doc->loadHTML($html);
		$main_header = '';
		foreach (self::$all_headers as $h) {
			$elements = $doc->getElementsByTagName($h);

			if ($elements->length > 0) {
				$main_header = $h;
				break;
			}
		}
		/* -------------------------------------------------------------------------- */
		$result = [];
		foreach ($doc->getElementsByTagName($main_header) as $element) {
			$text = $element->textContent;
			array_push($result, [
				'title' => $text,
				'link' => get_permalink($post) . '#' . $element->getAttribute('id'),
				'id' => $element->getAttribute('id')
			]);
		}

		return $result;
	}

	/**
	 * Remove any non numeric value from given string 
	 * then cast it to float
	 */
	public static function to_float(string $str, $default = -1): float
	{
		$res_str = \preg_replace('/[^0-9-.]/', '', $str);
		if (empty($res_str)) {
			return floatval($default);
		}
		return floatval($res_str);
	}
	/**
	 * Remove any non numeric value from given string 
	 * then cast it to int
	 */
	public static function to_int(string $str, $default = -1): int
	{
		$res_str = \preg_replace('/[^0-9-]/', '', $str);
		if (empty($res_str)) {
			return floatval($default);
		}
		return intval($res_str);
	}


	public static function stripslashes($value, $forced = false)
	{
		if (is_array($value)) {
			return array_map([__CLASS__, 'stripslashes'], $value);
		}
		if (is_string($value)) {
			$value = stripslashes($value);
			if ($forced && str_contains($value, '\\')) {
				$value = stripslashes($value);
			}
			return $value;
		}
		return $value;
	}
}
