<?php

namespace AzUtils;


abstract class az_date
{

	static function short_date($v)
	{
		$now = time();
		$your_date = (int) $v;
		$datediff = $now - $your_date;
		// Convert difference to full days
		$diff_days = (int) round($datediff / (60 * 60 * 24));

		if ($diff_days < 1) {
			// Less than one day difference
			return 'today';
		} elseif ($diff_days === 1) {
			// Exactly one day difference
			return 'yesterday';
		} elseif ($diff_days < 7) {
			// Under a week ago
			return $diff_days . ' days ago';
		} else {
			// Fallback: short date format, omitting the year if the same year as “now”
			$currentYear = date('Y', $now);
			$dateYear    = date('Y', $your_date);

			if ($currentYear === $dateYear) {
				// Same year, omit the year
				return date('m-d', $your_date);
			} else {
				// Different year, include the year
				return date('y-m-d', $your_date);
			}
		}
	}
}
