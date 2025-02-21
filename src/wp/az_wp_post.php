<?php

namespace AzUtils\wp;

use AzUtils\az_object;
use WP_Post;

trait az_wp_post
{
	public static function get_category_taxonomy_of_post_type(
		string|WP_Post|null $postType = null
	): string {
		if (empty($postType)) $postType = get_post_type();
		$taxlist = get_object_taxonomies($postType);
		$category_taxonomy = 'category';
		foreach ($taxlist as $tax) {
			if (str_contains($tax, 'category') || str_ends_with($tax, '_cat')) {
				$category_taxonomy = $tax;
				break;
			}
		}
		return strval($category_taxonomy);
	}

	/**
	 * check if we are in a blog based page 
	 */
	static function is_blog(): bool
	{
		$uri_parts = array_map('strtolower', array_filter(explode('/', $_SERVER['REQUEST_URI'])));

		foreach ($uri_parts as $part) {
			if ($part == 'blog') return true;
		}
		return false;
	}



	/**
	 * get id of given WP_Post or WC_Product
	 *
	 * @param [type] $input
	 * @return integer|null
	 */
	static function getId($input): int|null
	{
		if (is_a($input, 'WP_Post')) return $input->ID;
		if (is_a($input, 'WC_Product')) return $input->get_id();
		return null;
	}


	/**
	 * get a post of given type 
	 */
	static function get_post($input, string|array $post_type = ''): \WP_Post|null
	{
		if (is_a($input, 'WP_Post')) {
			$result = $input;
		} else {
			$post_id = static::getId($input);

			if ($post_id < 0) return null;

			$result = get_posts([
				'post_type' => $post_type,
				'post__in' => [$post_id],
				'limit' => 1,
			]);
			$result = end($result);
		}

		/**
		 * check if post type matches
		 */
		if (
			!empty($post_type)
			&& !empty($result)
			&& !in_array($result->post_type, az_object::wrap_array($post_type))
		) return null;

		return $result;
	}

	/**
	 * check if post type of input matches one of the allowedTypes
	 *
	 * @param [type] $input
	 * @param array|string $allowedTypes 
	 */
	static function postTypeMatches(object $input, array|string $allowedTypes)
	{
		if (is_object($input) && property_exists($input, 'post_type'))
			$input = $input->post_type;
		else if (is_a($input, 'WC_Product'))
			$input = 'product';
		else if (is_a($input, 'WP_Post'))
			$input = 'post';


		$allowedTypes = (array)$allowedTypes;
		if (sizeof($allowedTypes) === 0) return false;
		if (isset($allowedTypes[0]) && $allowedTypes[0] === 'any') return true;
		return in_array(strval($input), $allowedTypes);
	}
}
