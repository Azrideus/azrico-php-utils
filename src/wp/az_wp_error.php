<?php

namespace AzUtils\wp;

use AzUtils\az_object;
use WP_Error;
use WP_Post;

trait az_wp_error
{
	public static function detailed_error_list(
		mixed $wp_error,
		int $default_error_code = 400,
		int $depth = 0
	): array|null {
		if (is_wp_error($wp_error)) {
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
			return [$res];
		} else if ($depth == 0 && is_array($wp_error)) {
			$res = [];
			foreach ($wp_error as $sub_error) {
				$val = static::detailed_error_list($sub_error, $default_error_code, $depth + 1);
				if ($val != null && !empty($val)) $res[] = $val;
			}
		}
		return null;
	}
	/**
	 * calls `wp_send_json_error` with detailed error message using `detailed_error` 
	 */
	public static function send_json_detailed_error(
		mixed $wp_error,
		int $default_error_code = 400
	) {

		/**
		 * get details of the error
		 */
		$res = static::detailed_error_list($wp_error, $default_error_code);
		if (empty($res)) return false;
		return wp_send_json_error($res, $res['code']);
	}

	/**
	 * Either calls `send_json_detailed_error`
	 * or  `wp_send_json_success` with the OK message. 
	 */
	public static function send_json_detailed(
		mixed $response,
		mixed $default_ok_value = null,
		int $default_ok_code = null,
		int $default_error_code = 400
	) {
		if (is_wp_error($response)) return static::send_json_detailed_error($response, $default_error_code);
		if (empty($default_ok_value)) $default_ok_value = $response;
		return wp_send_json_success($default_ok_value, $default_ok_code);
	}
}
