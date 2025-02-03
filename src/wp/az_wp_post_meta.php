<?php

namespace AzUtils\wp;

trait az_wp_post_meta
{


	static function getMetaListOf($search, array $key_list): array
	{
		if (is_a($search, 'WP_Post')) $search = $search->ID;
		$res = [];
		foreach ($key_list as $key) {
			$res[$key] = self::getMetaOf($search, $key);
		}
		return $res;
	}
	static function getMetaOf($search, string $key)
	{

		assert(is_string($key) && strlen($key) > 0, 'invalid key for meta! got: ' . strval($key));
		assert(function_exists('get_post_meta'), 'get_post_meta function is not defined. are you in a wordpress environment?');

		if (
			is_object($search)
			&& property_exists($search, 'field')
			&& is_a($search->field, 'WP_Post')
		) {
			$search = $search->field;
		}


		/**
		 * WP_Post
		 */
		if (is_a($search, 'WP_Post')) {
			$search = $search->ID;
		}
		/**
		 * WP_Term
		 */
		else if (
			is_a($search, 'WP_Term')
		) {
			return get_term_meta($search->term_id, $key, true);
		}
		/**
		 * WC_Order_Item
		 */
		else if (is_a($search, 'WC_Order_Item')) {
			return $search->get_meta($key);
		}

		assert(is_numeric($search), 'could not load the post id to get its meta! got: ' . strval($search));

		return get_post_meta(
			$search,
			$key,
			true
		);
	}
	static function getMetaBoolOf($search, string $key): bool
	{
		$meta_value = self::getMetaOf(
			$search,
			$key
		);
		if (empty($meta_value)) return false;
		return filter_var(
			$meta_value,
			FILTER_VALIDATE_BOOL
		);
	}
	static function getMetaNumericOf($search, string $key, int $default = -1): int
	{
		$meta_value = self::getMetaOf(
			$search,
			$key
		);
		if (is_numeric($meta_value)) return intval($meta_value);
		return $default;
	}
}
