<?php

namespace AzUtils\wp;

use AzUtils\az_object;
use AzUtils\az_wp;
use WP_Post;

trait az_wp_searchquery
{
	/**
	 * Create a link with current search queries and additional parameters
	 * 
	 */
	static function make_link_with_search_queries(
		$extra_sq = array(),
		$exclude = array(),
		$include = array()
	) {
		$sq = self::get_search_query($exclude, $include);
		return az_wp::add_url_parameters(
			az_wp::get_current_url(),
			array_merge(
				$sq,
				$extra_sq
			)
		);
	}

	/**
	 * Get the search query (sq) from the URL
	 * can be used in forms to keep the search query inputs 
	 */
	static function get_search_query($exclude = array(), $include = array())
	{
		return array_filter($_GET, function ($key) use ($exclude, $include) {
			if (empty($key)) return false;
			if (!empty($exclude) && in_array($key, $exclude)) return false;
			if (!empty($include) && !in_array($key, $include)) return false;
			return true;
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * Convert search querty (sq) inputs to hidden inputs
	 * can be used in forms to keep the search query inputs 
	 */
	static function search_query_inputs(
		$exclude = array(),
		$include = array()
	) {
		$str = '<div hidden>';
		$sq = self::get_search_query($exclude, $include);
		foreach ($sq as $key => $value) {
			// Skip if empty
			if (empty($value)) continue;

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
