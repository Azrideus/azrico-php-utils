<?php

namespace AzUtils;


abstract class az_cache
{
	static $cache = [];

	/**
	 * name of the cache group
	 */
	abstract public static function getCacheGroup();

	public static function init()
	{
		assert(!empty(static::getCacheGroup()), 'getCacheGroup() is not implemented correctly');
		if (function_exists('add_action')) {
			add_action('litespeed_purged_all_object', [static::class, 'clear']);
			add_action('litespeed_purged_all', [static::class, 'clear']);
		}
	}

	static function set($key, $value, $wpcache = false)
	{
		static::$cache[$key] = $value;
		if ($wpcache && function_exists('wp_cache_set')) {
			wp_cache_set($key, $value, static::getCacheGroup(), 3600);
		}
		return $value;
	}
	static function get($key, $callback = null, $wpcache = false)
	{
		if (isset(static::$cache[$key])) return static::$cache[$key];

		if ($wpcache && function_exists('wp_cache_get')) {
			$cached_val = wp_cache_get($key, static::getCacheGroup(), true);
			if (!empty($cached_val)) return $cached_val;
		}
		if (null != $callback && is_callable($callback)) {
			return static::set($key,  $callback(), $wpcache);
		}
		return null;
	}
	public static function delete(string $key, $wpcache = false)
	{
		unset(static::$cache[$key]);
		if ($wpcache && function_exists('wp_cache_delete')) {
			wp_cache_delete($key,  static::getCacheGroup());
		}
		return true;
	}
	public static function clear()
	{
		$cache_keys = array_keys(static::$cache);
		foreach ($cache_keys as $ck) {
			static::delete($ck, false);
		}
		if (function_exists('wp_cache_supports') && wp_cache_supports('flush_group')) {
			return wp_cache_flush_group(static::getCacheGroup());
		}
		return true;
	}
}
