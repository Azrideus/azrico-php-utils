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
	 * @deprecated use get_id instead
	 * @param [type] $input
	 * @return integer|null
	 */
	static function getId($input): int|null
	{
		return self::get_id($input);
	}
	/**
	 * get id of given WP_Post or WC_Product 
	 * @param [type] $input
	 * @return integer|null
	 */
	static function get_id($input): int|null
	{
		if (empty($input)) return null;
		if ($input instanceof WP_Post) return $input->ID;
		if ($input instanceof WC_Product) return $input->get_id();
		if ($input instanceof WC_Order_Item_Product) return $input->get_product_id();

		if (\is_object($input)) {
			if (property_exists($input, 'ID')) return $input->ID;
			if (property_exists($input, 'id')) return $input->id;
		} else if (\is_array($input)) {
			if (isset($input['ID'])) return $input['ID'];
			if (isset($input['id'])) return $input['id'];
		}
		return null;
	}

	/**
	 * get cart_item by its key 
	 */
	static function get_cart_item(string|array $input): null|array
	{
		global $woocommerce;
		$cart = $woocommerce->cart->get_cart();
		if (is_array($input) && isset($input['key'])) $input = $input['key'];

		if (isset($cart[$input])) return $cart[$input];
		foreach ($cart as $cart_item_key => $cart_item) {
			if ($cart_item_key === $input)
				return $cart_item;
		}
		return null;
	}
	static function get_product($input): \WC_Product|null
	{
		if (empty($input)) return null;

		/* ------------------------------- id is given ------------------------------ */
		if (is_int($input) || (is_numeric($input) && intval($input) > 0))
			return wc_get_product(intval($input));

		/* ------------------------- product object is given ------------------------ */
		if ($input instanceof WC_Product)
			return $input;
		if ($input instanceof WC_Order_Item_Product)
			return $input->get_product();

		/* ------------------------------- find the id ------------------------------ */
		$pr_id = static::get_id($input);
		if (empty($pr_id)) return null;
		return wc_get_product($pr_id);
	}
	/**
	 * get a post of given type 
	 */
	static function get_post($input, string|array $post_type = ''): \WP_Post|null
	{
		if (empty($input)) return null;

		if ($input instanceof \WP_Post) {
			/* ------------------------------ post is given ----------------------------- */
			$result = $input;
			unset($input);
		} else if ($input instanceof \WC_Product) {
			/* ------------------------------ post is given ----------------------------- */
			$input = $input->get_id();
		}


		if (!empty($input) && \is_numeric($input)) {
			/* -------------------------------- get by id ------------------------------- */
			$post_id = intval($input);
			if ($post_id < 0) return null;

			/**
			 * if no $post_type is given use all post types to avoid exclude from search
			 * https://stackoverflow.com/questions/30554730/get-all-post-types-in-wordpress-in-query-posts
			 * https://wordpress.stackexchange.com/questions/13029/getting-only-a-specific-post-type-with-get-post
			 */
			if (empty($post_type)) $post_type =  get_post_types();
			$sq = [
				'post__in' => [$post_id],
				'limit' => 1,
				'post_type' => $post_type
			];
			$result = get_posts($sq);
			$result = end($result);
		}

		/**
		 * nothing found
		 */
		if (empty($result) || false == $result) return null;

		/**
		 * check if post type matches
		 */
		if (
			!empty($post_type)
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
