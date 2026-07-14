<?php

namespace AzUtils;


abstract class az_timing
{
	/** @var float Duration of last run in seconds (microtime float) */
	static $last_run = 0.0;

	/**
	 * Run a callable, time its execution, store elapsed seconds in self::$last_run,
	 * and return the callable's result.
	 *
	 * @param callable $fnc
	 * @return mixed
	 */
	public static function az_timing_run(callable $fnc)
	{
		$start = microtime(true);
		$result = $fnc();
		$end = microtime(true);
		self::$last_run = $end - $start;
		return $result;
	}
	public static function az_echo_timing_hidden_div()
	{
		echo '<div style="display:none;">';
		echo 'time: ' . self::$last_run . 's';
		echo '</div>';
	}
}
