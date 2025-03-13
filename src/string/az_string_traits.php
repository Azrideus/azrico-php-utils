<?php

namespace AzUtils\string;

use AzUtils\az_view;

trait az_string_traits
{
	static $traits_regex = '/\[\w+\]/';
	static $traits = [
		'color' => [
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
			'pcbgold' => 'rgb(203 150 19)',
			'primary' => 'var(--az-primary,var(--azpcb-primary,var(--ast-global-color-0)))'
		],
		'text-decoration-line' => [
			'underline' => 'underline',
			'overline' => 'overline',
			'line-through' => 'line-through',
		],
		'font-size' => [
			'large' => 'large',
			'small' => 'small',
			'medium' => 'medium',
		]
	];
	static function render_with_traits(string $elem, string $str, array $attr = [])
	{
		echo static::element_with_traits($elem, $str, $attr);
	}
	static function element_with_traits(string $elem, string $str, array $attr = [])
	{
		$raw_str = static::sanitize_traits($str);
		$traits = static::get_traits($str);
		$traits_str = '';
		foreach ($traits as $key => $value) {
			$traits_str = $key . ':' . end($value) . ';';
		}
		return az_view::element(
			$elem,
			$raw_str,
			[
				'style' => $traits_str
			],
			$attr
		);
	}
	static function sanitize_traits(string $str)
	{
		return trim(preg_replace(static::$traits_regex, '', $str));
	}
	static function has_any_trait(string $str)
	{
		preg_match(static::$traits_regex, $str, $matches);
		return !empty($matches);
	}
	static function has_trait(string $str, string $trait)
	{
		$trait_regex = '/\[' . $trait . '\]/';
		preg_match($trait_regex, $str, $matches);
		return !empty($matches);
	}
	static function get_traits(string $str)
	{
		$result = [];
		preg_match_all(static::$traits_regex, $str, $matches);
		foreach ($matches[0] as $m) {
			$match_value = substr($m, 1, -1); //remove brackets
			foreach (static::$traits as $list_name => $trait_list) {
				foreach ($trait_list as $tk => $value) {

					if ($match_value == $tk) {
						if (empty($result[$list_name])) $result[$list_name] = [];
						$result[$list_name][] = $value;
					}
				}
			}
		}
		return $result;
	}
}
