<?php

namespace AzUtils\wp;

use AzUtils\wp\az_wp_related_sorting;
use AzUtils\az_cache;
use AzUtils\az_object;
use AzUtils\az_string;
use AzUtils\az_wp;
use WP_Post;

const REL_SAME_SLUG      = 1 << 0;
const REL_SIMILAR_SLUG   = 1 << 1;
const REL_PAGEID         = 1 << 2;
const REL_CATEGORY 		 = 1 << 3;
const REL_ALL         = REL_SAME_SLUG | REL_SIMILAR_SLUG | REL_PAGEID | REL_CATEGORY;

trait az_wp_related
{
	/* ---------------------- Bit Flags for Relation Levels --------------------- */

	/**
	 * Replace tokens/placeholders like `product_link,post_link,...` with their permalink
	 * uses `get_related_family`
	 */
	public static function parse_related_placeholders($post, $link_str)
	{
		$related_family = az_wp::get_related_family($post);
		foreach ($related_family as $type => $related_post) {
			$repl = "{{$type}_link}";
			$link_str = str_replace($repl, get_permalink($related_post->ID), $link_str);
			$repl = "{{$type}_id}";
			$link_str = str_replace($repl, $related_post->ID, $link_str);
			$repl = "{{$type}_name}";
			$link_str = str_replace($repl, $related_post->name, $link_str);
		}
		return $link_str;
	}


	// /**
	//  * Find a list of blog posts for the given post 
	//  */
	// public static function get_related_blog_siblings($post)
	// {
	// 	$post = az_wp::get_post($post);
	// 	if (empty($post)) return [];
	// 	$siblings = [];

	// 	if ($post->post_parent > 0) {
	// 		/**
	// 		 * if post is a child of another post, related items are its siblings
	// 		 */
	// 		$siblings = az_wp::get_post_list(['post_parent' => $post->post_parent]);
	// 	} else {
	// 		/**
	// 		 * Related posts are posts in same parent category
	// 		 */
	// 		$self_category =
	// 			az_wp::get_primary_category_of($post, true);
	// 		if (!empty($self_category)) {
	// 			$sub_cats = az_wp::get_sub_categories($self_category, 'ids');

	// 			$siblings = az_wp::get_post_list(
	// 				[
	// 					'category__in' => [$self_category->term_id],
	// 					'category__not_in' => $sub_cats
	// 				]
	// 			);
	// 		}
	// 	}

	// 	return  az_wp_related_sorting::sort_related_posts($siblings);
	// 	// return \apply_filters('az_blog_siblings', $siblings, $post);
	// }

	/**
	 * get next and previous post of given post.
	 */
	public static function get_related_prev_next(
		$post,
		array|string|null $allowedTypes = null,
		$relationLevel = REL_SAME_SLUG | REL_PAGEID,
	) {

		/* ----------------------------- get from cache ----------------------------- */
		$postid = az_wp::get_id($post, true);
		$cache_key = 'related_prev_next_' . $postid
			. '_' . md5(join(',', az_object::comma_array($allowedTypes)))
			. '_' . strval($relationLevel);
		$cached = az_cache::get($cache_key, null, false);
		if (!empty($cached)) return $cached;

		$result = [
			'prev' => null,
			'next' => null
		];

		$post_list = az_wp_related::get_related_list_of(
			$post,
			$allowedTypes,
			true,
			$relationLevel
		);
		$current_index = null;

		foreach ($post_list as $i => $p) {
			if ($p->ID == $postid) {
				$current_index = $i;
				break;
			}
		}

		if ($current_index !== null) {
			$prev_post = $current_index > 0
				? $post_list[$current_index - 1]
				: null;
			$next_post = isset($post_list[$current_index + 1])
				? $post_list[$current_index + 1]
				: null;

			$result = [
				'prev' => $prev_post,
				'next' => $next_post
			];
		}
		az_cache::set($cache_key, $result, false);
		return $result;
	}





	/**
	 * find the primary related `$target_type` of the given post
	 * (ex. find `project` of given `product`) 
	 * 
	 * uses `get_related_list_of` and returns the first item of the list
	 */
	public static function get_related_main_of(
		int|string|object $search,
		string $target_type,
		$relationLevel = REL_SAME_SLUG
	): object|null {
		$current_post = az_wp::get_post($search, 'any');
		if (empty($current_post)) return null;
		$found_related_posts = static::get_related_list_of($current_post, $target_type, $relationLevel);
		return array_shift($found_related_posts);
	}


	/** 
	 * for the given post returns the related posts of each type (see `get_related_list_of`) 
	 * @return WP_Post[]
	 */
	public static function get_related_family(int|string|object $search, $relationLevel = REL_SAME_SLUG)
	{
		$members = ['product', 'post', 'project', 'product_doc', 'page'];
		$current_post = az_wp::get_post($search, 'any');
		$all_posts = static::get_related_list_of($current_post, $members, $relationLevel);
		$family = [];
		if (!empty($all_posts)) {
			foreach ($all_posts as $post) {
				if (empty($post) || !($post instanceof WP_Post)) continue;
				if (!in_array($post->post_type, $members)) continue;
				if (!static::check_all_posts_related([$current_post, $post])) continue;
				$family[$post->post_type] = $post;
			}
		}
		return $family;
	}


	/**
	 * get all related posts/products for the given post/product
	 * (products, posts, projects, videos)
	 *
	 * @param [type] $search
	 * @param array|string|null $allowedTypes
	 * @return \WP_Post[]
	 */
	public static function get_related_list_of(
		int|string|object $search,
		array|string|null $allowedTypes = null,
		bool $sorted = false,
		$relationLevel = REL_SAME_SLUG | REL_PAGEID,
	): array {
		if (empty($search)) return [];
		if (empty($allowedTypes)) $allowedTypes = get_post_types();
		$allowedTypes = az_object::comma_array($allowedTypes);
		$currentPost = az_wp::get_post($search, 'any');


		/* -------------------------------------------------------------------------- */
		/*                            Populate Search List                            */
		/* -------------------------------------------------------------------------- */
		$sl = static::get_related_keys_of($search, $allowedTypes, $relationLevel);
		$search_array = $sl['search_array'];
		$cache_key = $sl['cache_key'];


		$cached_ids = az_cache::get($cache_key, null, true);

		$foundPosts = [];
		if (is_array($cached_ids) && !empty($cached_ids)) {
			/* ----------------------------- Found in Cache ----------------------------- */
			$foundPosts = az_wp::get_post_list(
				['post__in' => $cached_ids],
				$allowedTypes,
				100
			);
		} else {
			/* ------------------------ link by primary category ------------------------ */
			if (
				!empty($currentPost)
				&& az_wp::get_meta_bool_of($currentPost, 'paginator_autolinkcategory')
			) {
				$primaryCategory = az_wp::get_primary_category_of($currentPost, true);
				if (!empty($primaryCategory)) {
					$sub_cats = az_wp::get_sub_categories($primaryCategory);
					$posts_by_cat = az_wp::get_post_list(
						[
							'category__in' => $primaryCategory->term_id,
							'category__not_in' => $sub_cats,
							'meta_query'     => array(
								'relation' => 'AND',
								[
									'key'     => 'paginator_autolinkcategory',
									'value'   => '1',
								]
							)
						],
						$allowedTypes
					);
					array_push(
						$foundPosts,
						...$posts_by_cat
					);
				}
			}
			/**
			 * do a pageid search on the searchlist
			 * pageid search can target attachments
			 */
			array_push(
				$foundPosts,
				...az_wp::get_post_list(
					['pageid' => $search_array],
					$allowedTypes,
				)
			);
			/**
			 * do a regular search on each element excluding attachments
			 * because we dont link attachments by their slug!
			 */
			$allowedTypesExclAttachment = array_diff($allowedTypes, ['attachment']);
			if (!empty($allowedTypesExclAttachment)) {
				foreach ($search_array as $s) {
					array_push($foundPosts, ...az_wp::get_post_list($s, $allowedTypesExclAttachment, 100, true));
				}
			}
		}



		/* ---------------------------- Remove Duplicates --------------------------- */
		$foundPosts = array_filter(array_unique($foundPosts, SORT_REGULAR));
		$uniqItems = [];
		$resultIDList = [];
		foreach ($foundPosts as $post) {
			$postId = az_wp::get_id($post);
			if (in_array($postId, $resultIDList)) continue;
			$uniqItems[] = $post;
			$resultIDList[] = $postId;
		}

		az_cache::set($cache_key, $resultIDList,  true);


		/* ------------------ Add Related Posts from Other Plugins ------------------ */
		$uniqItems = \apply_filters(
			'az_related_posts',
			$uniqItems,
			$currentPost,
			$allowedTypes,
		);

		if ($sorted)
			$uniqItems = az_wp_related_sorting::sort_related_posts($uniqItems);

		return $uniqItems;
	}

	/**
	 * checks if all posts in the given array are related posts
	 * 
	 * Meaning any of their related_keys match (see `get_related_keys_of`)
	 */
	public static function check_all_posts_related(array $posts)
	{
		$related_key_list = [];
		foreach ($posts as $post) {
			$post_obj = az_wp::get_post($post);
			$related_key_list[] = static::get_related_keys_of($post_obj)['s'];
		}
		/**
		 * All elements in the related_key_list should have at least one common element
		 */
		foreach ($related_key_list as $a) {
			foreach ($related_key_list as $b) {
				if (empty(array_intersect($a, $b))) {
					return false;
				}
			}
		}
		return true;
	}
	/**
	 * based on `paginator_pageid` and `slug` of given post, 
	 * this function will get a list of all ids that may be related to given post
	 * 
	 * @filters: `az_related_search_array`
	 */
	private static function get_related_keys_of(
		mixed $search,
		array|string|null $allowedTypes = null,
		$relationLevel = REL_SAME_SLUG | REL_PAGEID,
	): array {
		/* -------------------------------------------------------------------------- */
		/*                            get related of string                           */
		/* -------------------------------------------------------------------------- */
		if (is_string(($search))) {
			$searchList = [$search];
			$cache_key = 'r_srch_' . $search;
			return [
				'search_array' => $searchList,
				'cache_key' => $cache_key
			];
		}
		/* -------------------------------------------------------------------------- */
		/*                            get related of a post                           */
		/* -------------------------------------------------------------------------- */
		$currentPost = az_wp::get_post($search, 'any');
		if (empty($currentPost)) {
			return [
				'search_array' => [],
				'cache_key' => 'rp_empty_post'
			];
		}

		$postid = az_wp::get_id($currentPost);


		$types_str = join(',', az_object::comma_array($allowedTypes));
		$cache_key = 'rk_' . md5($postid . "_" . strval($relationLevel) . "_" . $types_str);
		return  az_cache::get('rk_list_' . $cache_key, function () use ($postid, $currentPost, $cache_key, $relationLevel) {
			/**
			 * id of current post and its slug is added to the search list
			 */
			$searchList = [];
			if ($relationLevel & REL_SAME_SLUG || $relationLevel & REL_SIMILAR_SLUG) {
				$searchList[] = $postid;
				$searchList[] = $currentPost->post_name;
			}
			if ($relationLevel & REL_SIMILAR_SLUG) {
				/**
				 * if post name is `sht35-arduino`
				 * it should also provide `sht35` as a search key
				 * in order to do that we remove certain keywords from the post name
				 */
				// $simplified_kw = explode("-", $post->post_name);
				// $simplified_kw = array_diff($simplified_kw, self::$board_keywords);
				// $searchList[] = join('-', $simplified_kw);  
				$slug_parts = explode('-', $currentPost->post_name);
				$searchList[] = $slug_parts[0]; //add first part of slug as well 
			}
			if ($relationLevel & REL_PAGEID) {
				/**
				 * add related pageids
				 * find all posts and products that share some of their related ids
				 * with the current product
				 */
				$metaPageids = az_wp::get_meta_comma_array_of($postid, 'paginator_targetpageid');
				$metaPageids = array_filter($metaPageids);
				if (empty($metaPageids)) {
					/**
					 * There is no `paginator_targetpageid` meta for this post
					 * so we will try to find `paginator_pageid` meta
					 */
					$metaPageids = az_wp::get_meta_comma_array_of($postid, 'paginator_pageid');
					$metaPageids = array_filter($metaPageids);
				}
				array_push($searchList, ...$metaPageids);
			}
			if ($relationLevel & REL_CATEGORY) {
				if (az_wp::get_meta_bool_of($postid, 'paginator_autolinkcategory')) {
					$primaryCategory = az_wp::get_primary_category_of($currentPost, true);
					$sub_cats = az_wp::get_sub_categories($primaryCategory);

					if (!empty($primaryCategory)) {
						$searchList[] = $primaryCategory->slug;
						$searchList[] = 'cat_' . $primaryCategory->term_id;
					}
				}
			}
			/* ----------------------- Add data from other plugins ---------------------- */
			$searchList = \apply_filters('az_related_search_array', $searchList, $currentPost, $relationLevel);
			$searchList = array_filter(array_unique($searchList, SORT_STRING));
			return [
				's' => $searchList,
				'search_array' => $searchList,
				'cache_key' => $cache_key,
				'key' => $cache_key
			];
		}, false);
	}
}
