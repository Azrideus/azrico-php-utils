<?php

namespace AzUtils;

use AzUtils\string\az_string_path;
use AzUtils\string\az_string_serialize;
use AzUtils\string\az_string_traits;

class az_string
{
	use az_string_path;
	use az_string_serialize;
	use az_string_traits;
	/**
	 * check if two string are not empty and equal ignorecase 
	 */
	static function eq($s1, $s2, $not_empty = true): bool
	{
		if ($not_empty && (empty($s1) || empty($s2))) return false;
		return (bool)(0 == strcasecmp($s1, $s2));
	}
	static function eq_loose($s1, $s2, $not_empty = true): bool
	{
		return (bool)self::eq(trim(strval($s1)), trim(strval($s2)), $not_empty);
	}
	/**
	 * converts string to lower and replaces white spaces with - 
	 */
	static function sanitize_search_string($s1): string
	{
		return preg_replace('/[\s_-]+/', '-', strtolower(strval($s1)));
	}
	/**
	 * after using `sanitize_search_string` will check if s1 contains s2 or s2 contains s2 
	 */
	static function loose_match($s1, $s2): bool
	{
		if (self::eq($s1, $s2)) return true;
		$s1 = self::sanitize_search_string($s1);
		$s2 = self::sanitize_search_string($s2);
		return  str_contains($s1, $s2) || str_contains($s2, $s1);
	}
	/**
	 * split s1 and s2 by - and check if any part of them match
	 */
	static function split_match($s1, $s2): bool
	{
		$s1 = explode('-', self::sanitize_search_string($s1));
		$s2 = explode('-', self::sanitize_search_string($s2));
		for ($i = 0; $i < sizeof($s1); $i++) {
			for ($j = 0; $j < sizeof($s2); $j++) {
				if ($s1[$i] == $s2[$j]) return true;
			}
		}
		return false;
	}
	/**
	 * join array values with comma 
	 */
	static function arrstr_join(array|string $val): string
	{
		if (is_array($val)) return join(",", array_values($val));
		return $val;
	}
	/**
	 * check if given string is a yes value 
	 */
	static function isYes($str): bool
	{
		if (empty($str)) return false;
		$str = \strval($str);
		return in_array(strtolower($str), ['درست', 'دارد', 'بله', 'on', 'true', 'yes', '1']);
	}
	/**
	 * check if given string is a no value 
	 */
	static function isNo($str): bool
	{
		$str = \strval($str);
		return in_array(strtolower($str), ['غلط', 'ندارد', 'نه', 'خیر', 'none', 'default', 'no', '0']);
	}
	/**
	 * check if the value is empty or a no value 
	 */
	static function isEmptyOrNo($value): bool
	{
		if (empty($value)) return true;
		/**
		 * if given array has no values its empty
		 */
		if (is_array($value) && empty(array_filter(array_values($value)))) return true;
		/**
		 * if given string is one of these values its empty
		 */
		if (
			is_string($value) && self::isNo($value)
		) return true;

		return false;
	}

	/**
	 * get the short name of a post by comparing its title and slug
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
		return self::trim_post_name($title);
	}
	public static function trim_post_name(string $title): string
	{
		return trim(trim($title, '*_- '));
	}
	/**
	 * Trim the number index prefix from given string 
	 */
	public static function trim_number_prefix(string $title): string
	{
		return \preg_replace('/^\d+\.?/', '', trim($title));
	}

	/**
	 * Remove any non numeric value from given string 
	 * then cast it to float
	 */
	public static function to_float(string $str): float
	{
		return az_parser::to_float($str);
	}
	/**
	 * Remove any non numeric value from given string 
	 * then cast it to int
	 */
	public static function to_int(string $str): float
	{
		return az_parser::to_int($str);
	}
	public static function has_digits(string $str): float
	{
		return preg_match('/\d/', $str);
	}

	static function truncate($string, $length, $dots = "...")
	{
		return (strlen($string) > $length) ? substr($string, 0, $length - strlen($dots)) . $dots : $string;
	}

	/**
	 * advanced expand string
	 *
	 * test[1|2|3] = test1,test2,test3
	 */
	static function expand($string, $expand_sep = "|", $join_sep = ",")
	{
		$matches = [];
		$result_string = $string;

		// Use preg_match_all to catch all bracketed sections
		preg_match_all('/\[.*?\]/', $string, $matches, PREG_OFFSET_CAPTURE);
		if (empty($matches[0])) return $result_string;
		foreach ($matches[0] as $match) {
			$result_string = \str_replace($match[0], '', $result_string);
		}

		$result_prefix = $result_string;

		$result_array = [];
		foreach ($matches[0] as $match) {
			$match_str = $match[0];
			$match_str  = substr($match_str, 1, strlen($match_str) - 2); // remove brackets
			$match_parts = explode($expand_sep, $match_str);
			foreach ($match_parts as $mp) {
				$result_array[] = $result_prefix . $mp;
			}
		}
		$result_string = implode($join_sep, $result_array);
		return $result_string;
	}

	/**
	 * return haysack's string part up until the needle
	 *
	 * @param string $haysack
	 * @param string $needle
	 * @return string
	 */
	static function find_upto(string $haysack, string $needle): string
	{
		$haysack_lower = strtolower($haysack);
		$needle_lower = strtolower($needle);
		/**
		 * if the name contains the slug, short name is the name part up until the slug
		 */
		if (str_contains($haysack_lower, $needle_lower)) {
			$endpos = strpos($haysack_lower, $needle_lower) + strlen($needle_lower);
			return substr($haysack, 0, $endpos);
		}
		if (str_contains($needle, '-')) {
			return self::find_upto($haysack, str_replace("-", "", $needle));
		}
		return $haysack;
	}
}
