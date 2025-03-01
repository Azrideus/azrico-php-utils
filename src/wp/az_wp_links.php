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
	static function add_parameters($url, $params = [])
	{
		$add_qmark = \str_contains($url, '?') ? '&' : '?';
		return $url . static::build_parameters($params, $add_qmark);
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
