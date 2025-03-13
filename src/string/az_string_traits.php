<?php

namespace AzUtils\string;

trait az_string_traits
{
	static $color_traits = [
		'red' => 'red',
		'blue' => 'blue',
		'green' => 'green',
		'yellow' => 'yellow',
		'orange' => 'orange',
		'purple' => 'purple',
		'pink' => 'pink',
		'black' => 'black',
		'white' => 'white',
		'gold' => 'gold',
		'pcbgold' => 'rgb(181, 159, 56)'
	];
	static function sanitize_traits(string $str)
	{
		return preg_replace('/\[.*\]/', '', $str);
	}
	static function has_any_trait(string $str)
	{
		preg_match('/\[.*\]/', $str, $matches);
		return !empty($matches);
	}
	static function has_trait(string $str, string $trait)
	{
		$trait_regex = '/\[' . $trait . '\]/';
		preg_match($trait_regex, $str, $matches);
		return !empty($matches);
	}

	static function get_trait_color(string $str)
	{
		foreach (static::$color_traits as $key => $value) {
			if (static::has_trait($str, $key)) return $value;
		}
		return '';
	}
}
