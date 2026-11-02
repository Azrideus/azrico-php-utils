<?php

declare(strict_types=1);

use AzUtils\az_cache;
use PHPUnit\Framework\TestCase;

// Minimal in-memory stand-in for the WP object cache
$GLOBALS['test_wp_cache'] = [];
function wp_cache_set($key, $value, $group = '', $expire = 0)
{
	$GLOBALS['test_wp_cache'][$group][$key] = $value;
	return true;
}
function wp_cache_get($key, $group = '', $force = false, &$found = null)
{
	$found = array_key_exists($key, $GLOBALS['test_wp_cache'][$group] ?? []);
	return $found ? $GLOBALS['test_wp_cache'][$group][$key] : false;
}

final class CacheTest extends TestCase
{
	protected function setUp(): void
	{
		az_cache::$cache = [];
		$GLOBALS['test_wp_cache'] = [];
	}

	public function testEmptyValueIsAHit(): void
	{
		$calls = 0;
		$compute = function () use (&$calls) {
			$calls++;
			return [];
		};
		$this->assertSame([], az_cache::get('k', $compute));
		$this->assertSame([], az_cache::get('k', $compute));
		$this->assertSame(1, $calls);
	}

	public function testEmptyValueFromWpCacheIsAHit(): void
	{
		az_cache::set('k', -1, true);
		az_cache::$cache = []; // next request: only the object cache survives
		$this->assertSame(-1, az_cache::get('k', fn() => $this->fail('recomputed'), true));
	}

	public function testMissComputes(): void
	{
		$this->assertNull(az_cache::get('missing'));
		$this->assertSame(5, az_cache::get('missing', fn() => 5, true));
	}
}
