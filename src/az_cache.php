<?php

namespace AzUtils;


class az_cache
{
	static $cache = [];

	/**
	 * name of the cache group
	 */
	public static function getCacheGroup()
	{
		return 'az_cache';
	}

	static function init()
	{
		assert(!empty(static::getCacheGroup()), 'getCacheGroup() is not implemented correctly');
		if (function_exists('add_action')) {
			add_action('litespeed_purged_all_object', [static::class, 'clear']);
			add_action('litespeed_purged_all', [static::class, 'clear']);
		}
	}

	static function set($key, $value, int|bool $wpcache = false)
	{
		static::$cache[$key] = $value;
		if ($wpcache && function_exists('wp_cache_set')) {
			if (\is_bool($wpcache)) $wpcache = 3600;
			wp_cache_set($key, $value, static::getCacheGroup(), $wpcache);
		}
		return $value;
	}
	/**
	 * Gets a cached value, or computes and caches it with the callback.
	 *
	 * Stored empty values (`[]`, `0`, `-1`, `''`) count as hits, so a lookup that found
	 * nothing is not recomputed.
	 *
	 * @param string $key The cache key.
	 * @param callable|null $callback Computes the value on a miss.
	 * @param int|bool $wpcache Also use the WP object cache, an int is the expiry in seconds.
	 * @return mixed The cached or computed value, null on a miss without callback.
	 */
	static function get($key, $callback = null,  int|bool $wpcache = false)
	{
		if (array_key_exists($key, static::$cache)) return static::$cache[$key];

		if ($wpcache && function_exists('wp_cache_get')) {
			$found = false;
			$cached_val = wp_cache_get($key, static::getCacheGroup(), true, $found);
			if ($found) return static::$cache[$key] = $cached_val;
		}
		if (null != $callback && is_callable($callback)) {
			return static::set($key,  $callback(), $wpcache);
		}
		return null;
	}
	static function delete(string $key,  int|bool $wpcache = false)
	{
		unset(static::$cache[$key]);
		if ($wpcache && function_exists('wp_cache_delete')) {
			wp_cache_delete($key,  static::getCacheGroup());
		}
		return true;
	}
	static function clear()
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
