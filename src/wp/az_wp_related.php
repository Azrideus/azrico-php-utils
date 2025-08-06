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
	 * Replace tokens like `product_link,post_link,...` with their permalink
	 * uses `get_related_family`
	 */
	public static function parse_related_tokens($post, $link_str)
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
	/**
	 * Find a list of blog posts for the given post 
	 */
	public static function get_related_blog_siblings($post)
	{
		$post = az_wp::get_post($post);
		if (empty($post)) return [];
		$siblings = [];

		if ($post->post_parent > 0) {
			/**
			 * if post is a child of another post, related items are its siblings
			 */
			$siblings = az_wp::get_post_list(['post_parent' => $post->post_parent]);
		} else {
			/**
			 * Related posts are posts in same parent category
			 */
			$self_category =
				az_wp::get_primary_category_of($post, true);
			if (!empty($self_category)) {
				$sub_cats = az_wp::get_sub_categories($self_category, 'ids');

				$siblings = az_wp::get_post_list(
					[
						'category__in' => [$self_category->term_id],
						'category__not_in' => $sub_cats
					]
				);
			}
		}
		return \apply_filters('az_blog_siblings', $siblings, $post);
	}


	public static function get_related_family(int|string|WP_Post $search): array
	{
		$members = ['product', 'post', 'project', 'product_doc', 'page'];
		$all_posts = static::get_related_list_of($search, $members);
		$family = [];
		foreach ($all_posts as $post) {
			if (in_array($post->post_type, $members)) {
				$family[$post->post_type][] = $post;
			}
		}
		return $family;
	}


	/**
	 * find the primary related `$target_type` of the given post
	 * (ex. find primary related `product` of given post) 
	 */
	public static function get_related_main_of(int|string|WP_Post $search, string $target_type): object|null
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

		$uniqItems = \apply_filters(
			'az_related_posts',
			$uniqItems,
			$currentPost,
		);

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
		$cache_key = 'rp_' . $postid;
		$cache_key .= '__' . join(',', $allowedTypes);

		return  az_cache::get('rp_list_' . $cache_key, function () use ($postid, $currentPost, $cache_key) {
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


			/* ----------------------- Add data from other plugins ---------------------- */
			$searchList = \apply_filters('az_related_search_array', $searchList, $currentPost);

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
		}, false);
	}
}
