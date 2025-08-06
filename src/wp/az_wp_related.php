<?php

namespace AzUtils\wp;

use AzUtils\az_cache;
use AzUtils\az_object;
use AzUtils\az_wp;
use WP_Post;

trait az_wp_related
{
	static $validRelatedTypes = array(
		'product',
		'post',
		'project',
		"product_doc",
		'page',
		'attachment'
	);
	/**
	 * find the primary related `$target_type` of the given post
	 * (ex. find primary related `product` of given post) 
	 */
	public static function get_main_related_of(string $target_type, $search): object|null
	{
		$current_post = az_wp::get_post($search, 'any');
		if (empty($current_post)) return null;

		$found_related_posts = static::get_related_list_of($current_post, $target_type);
		return array_shift($found_related_posts);
	}


	/**
	 * get all related posts/products for the given post or target
	 * (products, posts, projects, videos)
	 *
	 * @param [type] $search
	 * @param array|string|null $allowedTypes
	 * @return \WP_Post[]
	 */
	public static function get_related_list_of(
		int|string|WP_Post $search,
		array|string|null $allowedTypes = null,
	) {
		if (empty($search)) return [];
		if (empty($allowedTypes)) $allowedTypes = self::$validRelatedTypes;
		$allowedTypes = az_object::comma_array($allowedTypes);

		/* -------------------------------------------------------------------------- */
		/*                            Populate Search List                            */
		/* -------------------------------------------------------------------------- */
		$sl = static::get_related_search_array($search, $allowedTypes);
		$search_array = $sl['search_array'];
		$cache_key = $sl['cache_key'];
		$cache_key .= '__' . join(',', $allowedTypes);



		$cached_ids = az_cache::get($cache_key, null, true);


		if (is_array($cached_ids) && !empty($cached_ids)) {
			/* ----------------------------- Found in Cache ----------------------------- */
			$foundPosts = az_wp::get_post_list(
				['post__in' => $cached_ids],
				$allowedTypes,
				100
			);
		} else {
			/* ------------------------ link by primary category ------------------------ */
			$currentPost = az_wp::get_post($search, $allowedTypes);
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
			$postId = az_wp::get_id(($post));
			if (in_array($postId, $resultIDList)) continue;
			$uniqItems[] = $post;
			$resultIDList[] = $postId;
		}


		az_cache::set($cache_key, $resultIDList,  true);


		/* ------------------ Add Related Posts from Other Plugins ------------------ */
		if (\function_exists('apply_filters')) {
			$uniqItems = \apply_filters(
				'az_related_posts',
				$uniqItems,
				$currentPost,
			);
		}

		return $uniqItems;
	}

	/**
	 * based on `paginator_pageid` and `slug` of given post, 
	 * this function will get a list of all ids that may be related to given post
	 * 
	 * @filters:
	 * `az_related_search_array`
	 * @return:
	 * `[$postId,$postName, (paginator_targetpageid || paginator_pageid)]`
	 */
	private static function get_related_search_array(
		mixed $search,
		array|string|null $allowedTypes = null,
	): array {
		/* -------------------------------------------------------------------------- */
		/*                            get related of string                           */
		/* -------------------------------------------------------------------------- */
		if (is_string(($search))) {
			$searchList = [$search];
			$cache_key = 'rp_' . $search;
			return ['search_array' => $searchList, 'cache_key' => $cache_key];
		}
		/* -------------------------------------------------------------------------- */
		/*                            get related of a post                           */
		/* -------------------------------------------------------------------------- */
		$currentPost = az_wp::get_post($search, $allowedTypes);
		if (empty($currentPost)) return [];

		$postid = az_wp::get_id($currentPost);
		$cache_key = 'rp_' . $postid;


		/**
		 * id of current post and its slug is added to the search list
		 */
		$searchList = [];
		$searchList[] = $postid;
		$searchList[] = $currentPost->post_name;

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

		if (\function_exists('apply_filters')) {
			$searchList = \apply_filters('az_related_search_array', $searchList, $currentPost);
		}
		/**
		 * if post name is `sht35-arduino`
		 * it should also provide `sht35` as a search key
		 * in order to do that we remove certain keywords from the post name
		 */
		// $simplified_kw = explode("-", $post->post_name);
		// $simplified_kw = array_diff($simplified_kw, self::$board_keywords);
		// $searchList[] = join('-', $simplified_kw); 
		return [
			'search_array' => array_filter(array_unique($searchList, SORT_STRING)),
			'cache_key' => $cache_key
		];
	}
}
