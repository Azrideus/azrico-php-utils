<?php
class az_cache
{
	static $cache = [];
	static $cache_group = 'azcache';
	static function set($key, $value, $wpcache = false)
	{
		self::$cache[$key] = $value;
		if ($wpcache && function_exists('wp_cache_set')) {
			wp_cache_set($key, $value, self::$cache_group, 3600);
		}
		return $value;
	}
	static function get($key, $callback = null, $wpcache = false)
	{
		if (isset(self::$cache[$key])) return self::$cache[$key];
		if ($wpcache && function_exists('wp_cache_get')) {
			$cached_val = wp_cache_get($key, self::$cache_group, true);
			if (!empty($cached_val)) return $cached_val;
		}
		if (null != $callback && is_callable($callback)) {
			return self::set($key,  $callback(), $wpcache);
		}
		return null;
	}
	public static function delete(string $key, $wpcache = false)
	{
		unset(self::$cache[$key]);
		if ($wpcache && function_exists('wp_cache_delete')) {
			wp_cache_delete($key,  self::$cache_group);
		}
		return true;
	}
	public static function clear()
	{
		$cache_keys = array_keys(self::$cache);
		foreach ($cache_keys as $ck) {
			self::delete($ck, false);
		}
		if (function_exists('wp_cache_supports') && wp_cache_supports('flush_group')) {
			return wp_cache_flush_group(self::$cache_group);
		}
		return true;
	}
}
