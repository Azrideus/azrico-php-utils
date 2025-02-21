<?php

namespace AzUtils;

class az_object
{
	/**
	 * get data in either _POST or _GET
	 */
	static function getRequestData($name)
	{
		if (isset($_REQUEST[$name])) return $_REQUEST[$name];
		if (isset($_GET[$name])) return $_GET[$name];
		if (isset($_POST[$name])) return $_POST[$name];
		return null;
	}
	static function get($name)
	{
		return self::getRequestData($name);
	}
	/**
	 * wrap given input in array 
	 */
	static function wrap_array($data)
	{
		if (is_array($data)) return $data;
		if (empty($data)) return [];
		return [$data];
	}
	static function get_from(object|array $arr, ...$keys)
	{
		if (is_object($arr)) $arr = (array)$arr;
		foreach ($keys as $key) {
			if (isset($arr[$key])) return $arr[$key];
		}
		return null;
	}
	static function arr_push(array $arr, $key, $val): array
	{
		if (empty($arr[$key])) $arr[$key] = [];
		$arr[$key][] = $val;
		return $arr[$key];
	}
}
