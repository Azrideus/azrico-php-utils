<?php

namespace AzUtils\wp;

use AzUtils\az_object;
use AzUtils\az_wp;
use WP_Post;

trait az_wp_links
{

	static function get_current_url($params = [])
	{
		$res = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		return static::add_parameters($res, $params);
	}


	/**
	 * add given parameters to the url 
	 */
	static function add_parameters($url, $params = [])
	{
		$parsed_url = \parse_url($url);
		if ($parsed_url === false) {
			return $url; // Return original URL if parsing fails
		}
		// Extract components
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query    = isset($parsed_url['query']) ? $parsed_url['query'] : '';

		// Convert query string into an array
		parse_str($query, $query_array);
		// Merge existing query parameters with new ones
		$query_array = array_merge($query_array, $params);
		// Build new query string
		$new_query = http_build_query($query_array);
		// Construct final URL
		return $scheme . $host . $port . $path . (!empty($new_query) ? '?' . $new_query : '');
	}
	static function build_parameters($params = [], $ending_mark = '?')
	{
		$q = http_build_query($params);
		/**
		 * add question mark if needed
		 */
		if ($ending_mark != false && !empty($ending_mark) && !empty($final_params))
			$final_params = $ending_mark . $q;
		return $q;
	}
	static function ajaxlink($params = [])
	{
		return static::add_parameters(admin_url('admin-ajax.php'), $params);
	}
	static function actionlink($action, $params = [])
	{
		$redirect_to = static::get_current_url();
		$targetLink =  static::add_parameters(
			admin_url('admin-post.php'),
			[
				...$params,
				'action' => $action,
				'backto' => urlencode($redirect_to)
			]
		);
		return wp_nonce_url($targetLink, $action);
	}
	static function url_add_params(string $url, array $params = [])
	{
		if (str_contains($url, '?')) {
			$url_parts
				= explode('?', $url);
			$url = $url_parts[0];
			$existing_params = [1];
		} else {
			$existing_params = '';
		}
		$final_url = $url . '?'
			. (empty($params) ? '' :  http_build_query($params))
			. $existing_params;

		$final_url = trim($final_url, '?&');
		return $final_url;
	}
}
