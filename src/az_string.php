<?php

namespace AzUtils;


class az_string
{

	/**
	 * check if two string are not empty and equal ignorecase 
	 */
	static function eq($s1, $s2): bool
	{
		if (empty($s1) || empty($s2)) return false;
		return (bool)(0 == strcasecmp($s1, $s2));
	}
	static function eq_loose($s1, $s2): bool
	{
		return (bool)self::eq(trim(strval($s1)), trim(strval($s2)));
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
		return in_array(strtolower($str), ['درست', 'دارد', 'بله', 'on', 'true', 'yes', '1']);
	}
	/**
	 * check if given string is a no value 
	 */
	static function isNo($str): bool
	{
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
	public static function getPostShortName(array|object $input): string
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
		if (empty($title) || empty($slug)) return $title;
		return trim(self::findUpto($title, $slug));
	}

	static function fix_path($p)
	{
		if (DIRECTORY_SEPARATOR != '/') $p = str_replace('/', DIRECTORY_SEPARATOR, $p);
		else $p = str_replace('\\', DIRECTORY_SEPARATOR, $p);
		return $p;
	}
	/**
	 * return haysack's string part up until the needle
	 *
	 * @param string $haysack
	 * @param string $needle
	 * @return string
	 */
	static function findUpto(string $haysack, string $needle): string
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
			return self::findUpto($haysack, str_replace("-", "", $needle));
		}
		return $haysack;
	}
}
