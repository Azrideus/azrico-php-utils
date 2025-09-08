<?php

namespace AzUtils\wp;

use AzUtils\az_wp;



trait az_wp_related_category_extra
{

	/**
	 * if category has a page we link to that page otherwise link to the category page 
	 */
	public static function get_category_or_page_link($current_post_type, $cat, $forced_search = null)
	{
		$search_name = empty($$forced_search) ? $cat->slug : $forced_search;
		if ($current_post_type === 'post') {
			/**
			 * if we are in the blog, show a page that has BLOG as parent
			 * website/blog/ecg <- only chose this
			 * website/ecg
			 * website/medical/ecg
			 */
			$cat_page = az_wp::get_post_in_parent([
				'name' => $search_name,
			], 'page', 'blog');
		} else {
			/**
			 * if we are in the products, show a page that has doesnt have blog as parent
			 * website/blog/ecg (avoid this)
			 * website/ecg <- prefer this
			 * website/medical/ecg <- then prefer this
			 */
			$cat_page = az_wp::get_post_in_parent([
				'name' => $search_name,
			], 'page', 'blog');
		}

		if (!empty($cat_page)) return get_page_link($cat_page->ID);
		else return get_term_link($cat);
	}
}
