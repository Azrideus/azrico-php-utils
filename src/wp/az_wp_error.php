<?php

namespace AzUtils\wp;

use AzUtils\az_object;
use WP_Error;
use WP_Post;

trait az_wp_error
{
	public static function detailed_error(
		\WP_Error $wp_error
	): array {
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
		$res['message'] = $wp_error->get_error_message();
		return $res;
	}
	/**
	 * calls `wp_send_json_error` with detailed error message using `detailed_error`
	 *
	 * @param WP_Error $wp_error
	 * @return void
	 */
	public static function send_json_detailed_error(
		\WP_Error $wp_error
	) {
		$res = static::detailed_error($wp_error);
		wp_send_json_error($res, $wp_error->get_error_code());
	}
}
