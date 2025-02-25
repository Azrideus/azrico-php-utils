<?php

namespace AzUtils;

class az_object
{
	static $local_cache = [];
	/**
	 * get data in either $_REQUEST, $_GET or $_GET
	 */
	static function getRequestData(array|string|int $name)
	{
		if (\is_array($name)) $name = join('', $name);
		if (isset($_REQUEST[$name])) return $_REQUEST[$name];
		if (isset($_GET[$name])) return $_GET[$name];
		if (isset($_POST[$name])) return $_POST[$name];
		return null;
	}
	static function get($name)
	{
		return self::getRequestData($name);
	}
	static function setGlobal($name, $value, $clear = false)
	{
		az_object::$local_cache[$name] = $value;
		if ($clear) unset(az_object::$local_cache[$name]);
	}
	static function getGlobal($name)
	{
		if (isset(az_object::$local_cache[$name])) return az_object::$local_cache[$name];
		return null;
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
