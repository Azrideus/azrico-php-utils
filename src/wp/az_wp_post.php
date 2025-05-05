<?php

namespace AzUtils\wp;

use AzUtils\az_object;
use AzUtils\az_wp;
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
	 * get id from one of: `WP_Post, WC_Product, WC_Order_Item_Product,
	 * object->id , object->ID , array['id'] , array['ID']`
	 * @param [type] $input
	 * @param [type] $direct_parse treat input integer as id and return it directly
	 * @return integer|null
	 */
	static function get_id($input, bool $direct_parse = false): int|null
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
		} else if (is_int($input) && $direct_parse) return $input;
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
		if ($input instanceof \WC_Product)
			return $input;
		if ($input instanceof \WC_Order_Item_Product)
			return $input->get_product();

		/* ------------------------------- find the id ------------------------------ */
		$pr_id = static::get_id($input);
		if (empty($pr_id)) return null;
		return wc_get_product($pr_id);
	}
	/**
	 * get a post of given type , uses `get_post_list`
	 */
	static function get_post($search, string|array $allowedTypes = ''): \WP_Post|null
	{
		$postlist = static::get_post_list($search, $allowedTypes, 1);
		if (empty($postlist)) return null;
		return end($postlist);
	}
	/**
	 * search for a list of posts
	 *
	 * @return \WP_Post[]
	 */
	static function get_post_list(
		object|string|int|array $input,
		array|string $allowedTypes = '',
		int $limit = 100,
		bool $debug = false
	): array {

		/* -------------------------------------------------------------------------- */
		/*                              verify the input                              */
		/* -------------------------------------------------------------------------- */
		if (empty($input)) return [];

		if (is_object($input)) {
			if (
				property_exists($input, 'field')
				&& $input->field instanceof \WP_Post
			) {
				$input = $input->field;
			}
			if (
				property_exists($input, 'post')
				&& $input->post instanceof \WP_Post
			) {
				$input = $input->post;
			}
		}

		if ($input instanceof \WP_Post) {
			/**
			 * post is directly given
			 */
			return static::get_post_array_if_type_matches($input, $allowedTypes);
		} else if ($input instanceof \WC_Product) {
			/**
			 *  wc product is given
			 */
			$input = $input->get_id();
		} else if ($input instanceof \WC_Order) {
			/**
			 *  order is given
			 */
			$input = $input->get_id();
		} else if (!empty(az_wp::get_id($input))) {
			/**
			 * some object with ID is given
			 */
			$input = az_wp::get_id($input);
		}
		/**
		 * if no $post_type is given use all post types to avoid exclude from search
		 * https://stackoverflow.com/questions/30554730/get-all-post-types-in-wordpress-in-query-posts
		 * https://wordpress.stackexchange.com/questions/13029/getting-only-a-specific-post-type-with-get-post
		 */
		if (empty($allowedTypes)) {
			if (true === $debug) {
				error_log("[az_wp_post] no allowedTypes given, using all post types");
			}
			$allowedTypes =  get_post_types();
		}
		/**
		 * when searching for attachments, we search for status of inherit
		 */
		$allowedTypes = az_object::comma_array($allowedTypes);
		$postStatus = in_array('attachment', $allowedTypes) ? "inherit" : "publish";


		/**
		 * we cant search for posts and attachments at the same time
		 * so we have to seperate the searches
		 */
		if (in_array('attachment', $allowedTypes) && sizeof($allowedTypes) > 1) {
			if (($key = array_search('attachment', $allowedTypes)) !== false) {
				unset($allowedTypes[$key]);
			}
			if (true === $debug) {
				error_log("[az_wp_post] seperate search of attachment and posts");
			}
			return array_merge(
				static::get_post_list($input, 'attachment', $limit),
				static::get_post_list($input, $allowedTypes, $limit)
			);
		}

		/* ----------------------------- get post by id ----------------------------- */
		if (is_numeric($input)) {
			$foundPost = get_post($input);
			$foundPostTypeMatch = static::get_post_array_if_type_matches($foundPost, $allowedTypes);
			if (true === $debug) {
				error_log("[az_wp_post] search by post_id: " . strval($input));
				error_log("[az_wp_post] result: " . count($foundPostTypeMatch));
			}
			return $foundPostTypeMatch;
		}
		/* ---------------------------- get post by slug ---------------------------- */
		if (is_string($input)) {
			$input = array(
				'name'           => trim($input),
				'post_type'      => $allowedTypes,
				'post_status'    => $postStatus,
				'posts_per_page' => $limit
			);
		}
		if (!is_array($input)) return [];

		/* ------------------------------ pageid search ----------------------------- */
		if (array_key_exists('pageid', $input)) {
			$searchRgx = static::build_post_pageid_regex($input['pageid']);
			$final_search = array(
				'meta_query'     => array(
					array(
						'key'     => 'paginator_pageid',
						'compare' => 'REGEXP',
						'value'   => $searchRgx,
					),
				)
			);
			$result = static::get_post_list(
				$final_search,
				$allowedTypes,
				$limit
			);
			if (true === $debug) {
				error_log("[az_wp_post] search by pageid: " . print_r($final_search, true));
				error_log("[az_wp_post] result: " . count($result));
			}
			return $result;
		}

		$final_search =	array_merge(
			[
				'post_type'      => $allowedTypes,
				'post_status'    => $postStatus,
				'posts_per_page' => $limit,
			],
			$input
		);
		$result = get_posts(
			$final_search
		);
		if (true === $debug) {
			error_log("[az_wp_post] final_search: " . print_r($final_search, true));
			error_log("[az_wp_post] result: " . count($result));
		}
		return $result;
	}

	/**
	 * check if post type of input matches one of the allowedTypes
	 *
	 * @param [type] $input
	 * @param array|string $allowedTypes 
	 */
	static function post_type_matches(object $input, array|string $allowedTypes)
	{
		$type = '';
		if (is_object($input) && property_exists($input, 'post_type'))
			$type = $input->post_type;
		else if ($input instanceof \WC_Product)
			$type = 'product';
		else if ($input instanceof \Wp_Post)
			$type = 'post';
		else return false;

		$allowedTypes = (array)$allowedTypes;
		if (count($allowedTypes) === 0) return false;
		if (in_array('any', $allowedTypes)) return true;
		return in_array(strval($type), $allowedTypes);
	}
	/**
	 * if the post type of the input matches one of the allowedTypes return the input 
	 */
	static function get_post_if_type_matches(object|array $input, array|string $allowedTypes)
	{
		if (static::post_type_matches($input, $allowedTypes))
			return $input;
		return null;
	}
	/**
	 * if the post type of the input matches one of the allowedTypes return the input 
	 */
	static function get_post_array_if_type_matches(array|object $input, array|string $allowedTypes): array
	{
		$post_list = [];
		if (!is_array($input)) return $input = [$input];
		foreach ($input as $key => $post) {
			if (static::post_type_matches($post, $allowedTypes)) {
				$post_list[] = $post;
			}
		}
		return $post_list;
	}
	private static function build_post_pageid_regex(string|array $pageidlist): string
	{
		$pagesToSearch = is_array($pageidlist) ? $pageidlist : explode(',', $pageidlist);
		$anyOfRegex   = "";
		foreach ($pagesToSearch as $key => $p) {
			if (empty($p))
				continue;
			//fix the search regex
			$p = preg_replace("/[^A-Za-z0-9_-]/", '', $p);
			$p = str_replace("-", '\-', $p);
			/** 
			 * search for texts that
			 * start with, contain in middle , end with
			 * the given parameter
			 *
			 * simplified version of = (^test,)|(,test,)|(,test$)|(^test$)
			 * becomes = (^|,)(test)(,|$)
			 */
			$anyOfRegex .= "|(^|,)($p)(,|$)";
		}
		$anyOfRegex = preg_replace("/^\|/", '', $anyOfRegex);
		return "($anyOfRegex)";
	}
}
