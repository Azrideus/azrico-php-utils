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
		return $res . static::build_parameters($params);
	}
	static function build_parameters($params = [], $add_qmark = true)
	{
		$targetLink = '';
		foreach ($params as $key => $value) {
			$targetLink .= "&$key=$value";
		}
		$targetLink = trim($targetLink, '&');
		/**
		 * add question mark if needed
		 */
		if ($add_qmark && !empty($targetLink)) $targetLink = '?' . $targetLink;
		return $targetLink;
	}
	static function ajaxlink($params = [])
	{
		return admin_url('admin-ajax.php') . static::build_parameters($params);
	}
	static function actionlink($action, $params = [])
	{
		$redirect_to = static::get_current_url();
		$targetLink = admin_url('admin-post.php?action=' . $action)
			. static::build_parameters([...$params, 'backto' => urlencode($redirect_to)]);
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
