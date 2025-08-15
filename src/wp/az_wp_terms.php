<?php

namespace AzUtils\wp;

use AzUtils\az_cache;
use AzUtils\az_object;
use AzUtils\az_wp;
use WP_Post;

trait az_wp_category
{

	/* -------------------------------------------------------------------------- */
	/*                                    TERMS                                   */
	/* -------------------------------------------------------------------------- */
	/**  
	 * get a single category of a given taxonomy
	 * @return \WP_Term
	 */
	public static function get_term(
		mixed $search,
		$tax = 'product_cat'
	) {
		if (null == $search || empty($search)) return null;
		if ($search instanceof \WP_Term) {
			if ($search->taxonomy == $tax) {
				/**
				 * input is exactly what the user wants.
				 */
				return $search;
			} else {
				/**
				 * get category of other taxonomy from the given category
				 * ex: get `product category` from a `post category`
				 */
				$res = static::get_term($search->slug, $tax);
				if (!empty($res)) return $res;
				$res = static::get_term($search->name, $tax);
				if (!empty($res)) return $res;
				return null;
			}
		}

		if (\is_numeric($search)) {
			$search = intval($search);
			return get_term_by('term_id', $search, $tax);
		}
		$search = strval($search);
		return get_term_by('slug', ($search), $tax);
	}
	/** 
	 * get subcategories of a given category 
	 */
	public static function get_sub_categories(\WP_Term $category, $fields = 'ids')
	{
		return get_terms(
			$category->taxonomy,
			[
				'parent' => $category->term_id,
				'hide_empty' => false,
				'fields' => $fields,
				'orderby' => 'name',
				'nopaging' => true
			]
		);
	}
	/** 
	 * search for categories based on search criteria
	 */
	static function find_categories(
		array|object $search,
	) {
		$search = shortcode_atts(
			array(
				'post_type'   => 'auto',
				'categories' => [],
				'include' => [],
				'exclude' => [],
				'hide_empty' => 1,
				'parent' => 0,
			),
			$search
		);
		/* ------------------ suport for custom category taxonomies ----------------- */

		$category_taxonomy = az_wp::get_category_taxonomy_of_post_type($search['post_type']);

		if (!empty($search['categories'])) {
			$cats = explode(',', $search['categories']);
			return array_map(
				function ($cat_search) use ($category_taxonomy) {
					return az_wp::get_category($cat_search, $category_taxonomy);
				},
				$cats
			);
		}
		/* ----------------------------- parse parent id ---------------------------- */

		if (!empty($search['parent'])) {
			if ($search['parent'] === 'this') {
				$current_category = get_queried_object();
				if (is_a($current_category, 'WP_Term')) {
					$search['parent'] =  $current_category->term_id;
				}
			} else {
				$parent_cat =
					az_wp::get_category(
						$search['parent'],
						$category_taxonomy
					);
				if (empty($parent_cat)) unset($search['parent']);
				else
					$search['parent'] = $parent_cat->term_id;
			}
		}


		$sq =  array(
			'hide_empty' => filter_var(
				$search['hide_empty'],
				FILTER_VALIDATE_BOOLEAN
			),

		);
		if (isset($search['parent']) && $search['parent'] >= 0) {
			$sq['parent'] = $search['parent'];
		}
		if (!empty($search['exclude'])) {
			$sq['exclude'] = az_object::comma_array($search['exclude']);
		}
		if (!empty($search['include'])) {
			$sq['include'] = az_object::comma_array($search['include']);
		}

		$sq = array_merge(
			array(
				'orderby'  => 'slug',
				'order'    => 'DESC'
			),
			$sq,
			array(
				'taxonomy' => $category_taxonomy,
				'number'  => 100,
			)
		);
		return get_terms($sq);
	}


	/**
	 * get a list of category ids from a list of categories
	 * @param array $cat_list
	 * @param string $tax
	 * @return WP_Term[]
	 */
	public static function map_category_id_list(
		array $cat_list,
		$tax = 'product_cat'
	) {
		return array_map(
			function ($cat) use ($tax) {

				$found_cat = static::get_category($cat, $tax);
				if ($found_cat instanceof \WP_Term)
					return $found_cat->term_id;
				return null;
			},
			$cat_list
		);
	}
	/**  
	 * get a single category of a given taxonomy
	 * @return \WP_Term
	 */
	public static function get_category(
		mixed $search,
		$tax = 'product_cat'
	) {
		return static::get_term($search, $tax);
	}
	/**  
	 * get all categories of a given post
	 * @return  \WP_Error|\WP_Term[]
	 */
	static function get_categories_of(
		array|object|int $search,
		bool $add_parent_cats = false
	) {
		if (empty($search)) $post =  az_wp::get_post(get_the_ID());
		else $post = az_wp::get_post($search);

		if (empty($post)) {
			$err = "get_categories_of failed because post is empty";
			$err .= " \n searched for: " . json_encode($search);
			$err .= " \n got : " . json_encode($post);
			return new \WP_Error($err);
		}

		$post_id = az_wp::get_id($post);
		$cache_key = 'cats_' . $post_id . ($add_parent_cats ? "_p" : "_n");
		return az_cache::get($cache_key, function () use ($post_id, $add_parent_cats) {

			$ptype = az_wp::get_category_taxonomy_of_post_type(get_post_type($post_id));
			$result_cats = wp_get_object_terms($post_id, $ptype, ['fields' => 'all']);

			if ($add_parent_cats) {
				foreach ($result_cats as $cat) {
					if ($cat->parent > 0) {
						$parent = static::get_category(
							$cat->parent,
							$cat->taxonomy
						);
						if (!in_array($parent, $result_cats)) {
							$result_cats[] = $parent;
						}
					}
				}
			}
			return $result_cats;
		});
	}
	/**
	 * get primary category of a given post
	 * @param [] $search
	 * @param [bool] $getFirst use the first category if no primary category is set
	 * @return null|\WP_Term
	 */
	public static function get_primary_category_of(
		string|int|object $search,
		bool $getFirst = false
	) {

		$post = az_wp::get_post($search);
		$tax = az_wp::get_category_taxonomy_of_post_type($post->post_type);

		if (function_exists('yoast_get_primary_term_id')) {
			$primary_category_id = yoast_get_primary_term_id($tax, $search);
		} else {
			$primary_category_id = az_wp::get_meta_of($search, '_yoast_wpseo_primary_category');
		}

		if (!empty($primary_category_id)) {
			$primary_category = az_wp::get_category($primary_category_id, $tax);
			if ($primary_category instanceof \WP_Term) {
				return $primary_category;
			}
		}

		/** 
		 * If primary category is not set by Yoast SEO, 
		 * we return the most child category
		 */
		$categories = az_wp::get_categories_of($post);
		// Build a map of term_id => term object
		$cat_map = [];
		foreach ($categories as $cat) {
			$cat_map[$cat->term_id] = $cat;
		}
		// Find categories that are not parents of any other category in the set
		$parent_ids = [];
		foreach ($categories as $cat) {
			if ($cat->parent && isset($cat_map[$cat->parent])) {
				$parent_ids[$cat->parent] = true;
			}
		}
		// The first category that is not a parent (i.e., a child-most category)
		foreach ($categories as $cat) {
			if (! isset($parent_ids[$cat->term_id])) {
				return $cat;
			}
		}


		if ($getFirst && !empty($categories)) {
			return reset($categories);
		}

		return null;
	}
}
