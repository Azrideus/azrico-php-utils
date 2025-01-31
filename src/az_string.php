<?php
class az_string
{

	/**
	 * check if two string are not empty and equal ignorecase 
	 */
	static function str_eq($s1, $s2): bool
	{
		if (empty($s1) || empty($s2)) return false;
		return (bool)(0 == strcasecmp($s1, $s2));
	}
	static function str_eq_loose($s1, $s2): bool
	{
		return (bool)self::str_eq(trim(strval($s1)), trim(strval($s2)));
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
	static function str_loose_match($s1, $s2): bool
	{
		if (self::str_eq($s1, $s2)) return true;
		$s1 = self::sanitize_search_string($s1);
		$s2 = self::sanitize_search_string($s2);
		return  str_contains($s1, $s2) || str_contains($s2, $s1);
	}
	/**
	 * split s1 and s2 by - and check if any part of them match
	 */
	static function str_split_match($s1, $s2): bool
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
}
