<?php

namespace AzUtils\wp;

use AzUtils\az_cache;
use AzUtils\az_wp;
use WP_Post;

trait az_wp_category
{
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
	 * get a single category of a given taxonomy
	 * @return \WP_Term
	 */
	public static function get_category(
		string|int|object $search,
		$tax = 'product_cat'
	) {
		if (is_a($search, 'WP_Term')) {
			if ($search->taxonomy == $tax) {
				/**
				 * Input is what the user wants.
				 */
				return $search;
			} else {
				/**
				 * Get category of other taxonomy from the given category
				 */
				$search = $search->slug;
			}
		}

		if (\is_int($search)) {
			$search = intval($search);
			return get_term_by('id', $search, $tax);
		}
		$search = strval($search);
		return get_term_by('slug', ($search), $tax);
	}
	/**  
	 * @return  \WP_Error|\WP_Term[]
	 */
	static function get_categories_of(
		object $search,
		bool $add_parent_cats = false
	) {
		if (empty($search)) $post =  az_wp::get_post(get_the_ID());
		else $post = az_wp::get_post($search, 'any');

		if (empty($post)) {
			$err = "findCategoriesOfPost failed because post is empty, searched for: " . json_encode($search);
			return new \WP_Error($err);
		}

		$post_id = az_wp::getId($post);
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
		$categories = static::get_categories_of($search);
		$primary_category_id = az_wp::getMetaOf($search, '_yoast_wpseo_primary_category');

		if (!empty($primary_category_id) && !is_wp_error($categories)) {
			foreach ($categories as $cat) {
				if ($primary_category_id == $cat->term_id)
					return $cat;
			}
		}
		if (true == $getFirst)
			return reset($categories);
		return null;
	}
}
