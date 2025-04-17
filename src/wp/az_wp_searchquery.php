<?php

namespace AzUtils\wp;

use AzUtils\az_object;
use AzUtils\az_wp;
use WP_Post;

trait az_wp_searchquery
{
	/**
	 * Convert search querty (sq) inputs to hidden inputs
	 * can be used in forms to keep the search query inputs 
	 */
	static function search_query_inputs(
		$exclude = array(),
		$include = array()
	) {
		$str = '<div hidden>';
		foreach ($_GET as $key => $value) {
			// Skip if excluded or empty
			if (empty($value)) continue;
			if (!empty($exclude) && in_array($key, $exclude)) continue;
			if (!empty($include) && !in_array($key, $include)) continue;

			// Handle arrays (e.g. checkboxes or taxonomy filters)
			if (is_array($value)) {
				foreach ($value as $sub_value) {
					$str .= '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($sub_value) . '">' . "\n";
				}
			} else {
				$str .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">' . "\n";
			}
		}
		$str .= '</div>';
		return $str;
	}
}
