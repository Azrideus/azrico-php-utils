<?php

namespace AzUtils;

class az_object
{
	static $local_cache = [];


	/**
	 * Evaluate given function or return the value 
	 */
	static function eval($fn)
	{
		if (\is_callable($fn)) return $fn();
		return $fn;
	}

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
	static function array_push(array $arr, $key, $val): array
	{
		if (empty($arr[$key])) $arr[$key] = [];
		$arr[$key][] = $val;
		return $arr[$key];
	}

	/**
	 * Remove given element from array 
	 */
	static function array_remove(array $arr, $rm): array
	{
		if (!in_array($rm, $arr)) return $arr;
		return array_filter($arr, static function ($element) use ($rm) {
			return $element !== $rm;
		});
	}
	static function comma_array($val): array
	{
		if (is_string($val)) {
			$val = trim(trim($val), ",");
			return array_filter(explode(",", $val));
		} else if (is_array($val)) {
			return $val;
		}
		return [strval($val)];
	}
	static function singular($a)
	{
		if (is_array($a) && sizeof($a) === 1) {
			return end($a);
		}
		return $a;
	}
}
