<?php

namespace AzUtils;

class az_object
{
	static $local_cache = [];


	/**
	 * Evaluate given function or return the value 
	 */
	static function eval(mixed $fn)
	{
		if (\is_callable($fn)) return $fn();
		return $fn;
	}


	/**
	 * @deprecated use get_request_data
	 */
	static function getRequestData(array|string|int $name, $sanitize = false)
	{
		return self::get_request_data($name, $sanitize);
	}
	static function get(array|string|int $name, $sanitize = false)
	{
		return self::get_request_data($name, $sanitize);
	}

	/**
	 * get data in either $_REQUEST, $_GET or $_GET
	 */
	static function get_request_data(array|string|int $name, $sanitize = false)
	{
		if (\is_array($name)) $name = join('', $name);
		$val = null;
		if (isset($_REQUEST[$name])) $val = $_REQUEST[$name];
		if (isset($_GET[$name])) $val = $_GET[$name];
		if (isset($_POST[$name])) $val = $_POST[$name];

		/* -------------------------------- sanitize -------------------------------- */
		if ($val != null && $sanitize) {
			if (function_exists('sanitize_text_field')) {
				$val = \is_array($val) ? array_map('sanitize_text_field', $val) : sanitize_text_field($val);
			}
		}
		return $val;
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
	/**
	 * get one of the $keys from given object or array
	 */
	static function get_from(object|array $arr, ...$keys)
	{
		if (empty($arr) || empty($keys)) return null;
		if (is_object($arr)) $arr = (array)$arr;
		foreach ($keys as $key) {
			if (empty($key)) continue;
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

	/* ------------------------------- Get Search ------------------------------- */
	/**
	 * get one of 's', 'search', 'id', 'name', 'cat', 'category', 'f' from given obj
	 * 
	 * will use $default if loaded value is not found or its null
	 * 
	 * usefull for getting search parameters from $_GET,$_REQUEST or $atts
	 */
	static function get_search_of(array|object $obj, mixed $default = \null, ...$keys): mixed
	{
		if (empty($obj)) return null;
		if (empty($keys)) {
			$keys = [
				's',
				'search',
				'id',
				'name',
				'cat',
				'category',
				'categories',
				'p',
				'product',
				'products',
				'f',
			];
		}
		$v = self::get_from($obj, ...$keys);
		if ($v === null && $default != null) {
			$v = static::eval($default);
		}
		if ($v === null) {
			return null;
		}
		return $v;
	}
}
