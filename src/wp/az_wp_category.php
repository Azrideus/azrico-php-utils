<?php

namespace AzUtils\wp;

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
		if (is_a($search, 'WP_Term')) return $search;
		if (is_numeric($search)) {
			$search = intval($search);
			return get_term_by('id', $search, $tax);
		}
		$search = strval($search);
		return get_term_by('slug', ($search), $tax);
	}

	static function get_categories_of(
		object $search,
		bool $add_parent_cats = false
	) {
		if (empty($search)) $post = self::findPost(get_the_ID());
		else $post = self::findPost($search, 'any');

		if (empty($post)) {
			$err = "findCategoriesOfPost failed because post is empty, searched for: " . json_encode($search);
			return new \WP_Error($err);
		}
		$ptype = az_wp::get_category_taxonomy_of_post_type(get_post_type($post));
		$result_cats = wp_get_object_terms(az_wp::getId($post), $ptype, ['fields' => 'all']);

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
	}
}
