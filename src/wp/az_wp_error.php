<?php

namespace AzUtils\wp;

use AzUtils\az_object;
use WP_Error;
use WP_Post;

trait az_wp_error
{
	public static function detailed_error(
		mixed $wp_error,
		int $default_error_code = 400
	): array {

		if (!is_wp_error($wp_error)) {
			return [];
		}
		$res = [
			'code' => $wp_error->get_error_code(),
			'message' => $wp_error->get_error_message()
		];
		$errors = $wp_error->get_error_data();
		if (is_array($errors)) {
			foreach ($errors as $key => $value) {
				if (is_array($value)) {
					$res[$key] = implode(',', $value);
				} else {
					$res[$key] = $value;
				}
			}
		}
		$res['code'] = $wp_error->get_error_code();
		if (empty($res['code'])) $res['code'] = $default_error_code;
		$res['message'] = $wp_error->get_error_message();
		return $res;
	}
	/**
	 * calls `wp_send_json_error` with detailed error message using `detailed_error` 
	 */
	public static function send_json_detailed_error(
		mixed $wp_error,
		int $default_error_code = 400
	) {
		if (!is_wp_error($wp_error)) {
			return false;
		}
		$res = static::detailed_error($wp_error, $default_error_code);
		return wp_send_json_error($res, $res['code']);
	}
}
