<?php

namespace AzUtils\wp;

use AzUtils\az_wp;

trait az_wp_post_meta
{
	/**
	 * @deprecated
	 */
	static function getMetaBoolOf($search, string $key): bool
	{
		return static::get_meta_bool_of(
			$search,
			$key
		);
	}

	/**
	 * @deprecated
	 */
	static function getMetaNumericOf($search, string $key, int $default = -1): int|float
	{
		return static::get_meta_numeric_of(
			$search,
			$key,
			$default
		);
	}


	/**
	 * @deprecated
	 */
	static function getMetaOf($search, string $key)
	{
		return static::get_meta_of($search, $key);
	}
	/**
	 * @deprecated
	 */
	static function getMetaListOf($search, array $key_list): array
	{
		return static::get_meta_list_of($search, $key_list);
	}


	static function get_meta_list_of($search, array $key_list): array
	{
		if (is_a($search, 'WP_Post')) $search = $search->ID;
		$res = [];
		foreach ($key_list as $key) {
			$res[$key] = static::get_meta_of($search, $key);
		}
		return $res;
	}


	static function get_meta_bool_of($search, string $key): bool
	{
		$meta_value = static::get_meta_of(
			$search,
			$key
		);
		if (empty($meta_value)) return false;
		return filter_var(
			$meta_value,
			FILTER_VALIDATE_BOOL
		);
	}
	static function get_meta_numeric_of($search, string $key, int $default = -1): int|float
	{
		$meta_value = static::get_meta_of(
			$search,
			$key
		);
		if (is_int($meta_value)) return intval($meta_value);
		if (is_float($meta_value)) return floatval($meta_value);
		if (is_numeric($meta_value)) return intval($meta_value);
		return $default;
	}

	static function get_meta_of($search, string $key)
	{

		if (!is_numeric($search)) {
			/**
			 * WP_Term
			 */
			if (
				$search instanceof \WP_Term
			) {
				return get_term_meta($search->term_id, $key, true);
			}
			/**
			 * WC_Order_Item
			 */
			if ($search instanceof \WC_Order_Item) {
				return $search->get_meta($key);
			}
			/**
			 * WC_Order
			 */
			if ($search instanceof \WC_Order) {
				return $search->get_meta($key);
			}
			/**
			 * WP_Post
			 */
			$search = az_wp::get_post($search, 'any');
			if ($search instanceof \WP_Post)
				$search = $search->ID;
		}


		if (!is_numeric($search)) {
			throw new \Exception(
				"cant get the post id to get meta ({$key}) " .
					' got: ' . strval($search) .
					' search: ' . \json_encode($search)
			);
		}
		return get_post_meta(
			$search,
			$key,
			true
		);
	}
	static function set_meta_of($search, string $key, $value)
	{
		if (!is_numeric($search)) {
			/**
			 * WC_Order
			 */
			if (
				$search instanceof \WC_Order
				|| $search instanceof \WC_Order_Item
			) {
				$search->update_meta_data($key, $value);
				return $search->save_meta_data();
			}
		}

		if (!is_numeric($search)) {
			/**
			 * WP_Post
			 */
			$search = az_wp::get_post($search, 'any');
			if ($search instanceof \WP_Post)
				$search = $search->ID;
		}
		if (!is_numeric($search)) throw new \Exception('could not load the post id to set its meta! got: ' . strval($search));
		if (null == $value) {
			return delete_post_meta(
				$search,
				$key
			);
		}
		return update_post_meta(
			$search,
			$key,
			$value
		);
	}
}
