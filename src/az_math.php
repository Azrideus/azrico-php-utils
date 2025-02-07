<?php

namespace AzUtils;


class az_math
{
	public static function ceil_plus(float $value, ?int $precision = null): float
	{
		if (null === $precision) {
			return (float) ceil($value);
		}
		if ($precision < 0) {
			throw new \RuntimeException('Invalid precision');
		}

		$reg = $value + 0.5 / (10 ** $precision);
		return round($reg, $precision, $reg > 0 ? PHP_ROUND_HALF_DOWN : PHP_ROUND_HALF_UP);
	}
	public static function digits(int|float $number): int
	{
		$digits = str_split(strval($number));
		$digitCount = count($digits);
		return $digitCount;
	}
	public static function has_fraction(int|float $number): bool
	{
		return in_array(str_split(strval($number)), ['.', ',']);
	}
}
