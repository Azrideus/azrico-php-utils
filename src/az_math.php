<?php

namespace AzUtils;


class az_math
{
	static $basic_operator_regex = "/(>=|<=|<|>|!=|==|===)/";



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

	public static function get_operator(string $operation): string
	{
		preg_match(self::$basic_operator_regex, $operation, $operator_match);
		if (!$operator_match) {
			throw new \InvalidArgumentException("Invalid operation format");
		}
		return $operator_match[0];
	}
	public static function split_operands(string $operation): array
	{
		$operands = preg_split(self::$basic_operator_regex, $operation);
		if (count($operands) !== 2) {
			throw new \InvalidArgumentException("Invalid number of operands");
		}
		return [trim($operands[0]), trim($operands[1])];
	}

	/**
	 * check if a basic `>,<,<=,>=,!=` operation is satisfied
	 *
	 * @param string $operation
	 * @return boolean
	 */
	public static function is_satisfied(string $operation): bool
	{

		$operator = self::get_operator($operation);
		$operands = self::split_operands($operation);

		// Trim and convert operands to numbers
		$left = $operands[0];
		$right = $operands[1];

		if (!is_numeric($left) || !is_numeric($right)) {
			/* ------------------------- Non Numeric Comparison ------------------------- */
			switch ($operator) {
				case '==':
				case '===':
					return az_string::eq($left, $right);
				case '!=':
					return !az_string::eq($left, $right);
			}
			return false;
		}
		/* --------------------------- Numeric Comparison --------------------------- */
		$left = (float) $left;
		$right = (float) $right;
		// Evaluate the operation
		switch ($operator) {
			case '==':
			case '===':
				return $left == $right;
			case '!=':
				return $left != $right;
			case '>':
				return $left > $right;
			case '<':
				return $left < $right;
			case '>=':
				return $left >= $right;
			case '<=':
				return $left <= $right;
			default:
				throw new \InvalidArgumentException("Unknown operator");
		}
	}

	/**
	 * convert the given number (ex: 25) into a range that can be used for searching:
	 * 25
	 * 250-259 (1)
	 * 2500-2599 (2)
	 * 25000-25999 (3)
	 * @param num
	 * @param count number of zeros to add
	 * @returns
	 */
	public static function search_range(int $num, int $count): array
	{
		$rangeResults = [];

		//Exact match
		$rangeResults[] = ['eq' => $num];

		for ($index = 1; $index <= $count; $index++) {
			$startingNumber = $num * pow(10, $index);
			$ninesToAdd = (int)str_repeat('9', $index);
			$endingNumber = $startingNumber + $ninesToAdd;

			$rangeResults[] = [
				'gte' => $startingNumber,
				'lte' => $endingNumber,
			];
		}

		return $rangeResults;
	}
}
