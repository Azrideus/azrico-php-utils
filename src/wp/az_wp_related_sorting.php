<?php

namespace AzUtils\wp;

use AzUtils\az_cache;
use AzUtils\az_i18n;
use AzUtils\az_wp;

trait az_wp_related_sorting
{
	static $board_keywords = array('arduino', 'stm32');

	/**
	 * Sort given posts based on the `getSortIndex` function
	 * lower numbers are shown first
	 */
	static function sort_related_posts(array $postlist, array $parameters = ['index']): array
	{
		//TODO FIX SORT

		$compare_functions = [];
		if (in_array('parent', $parameters)) {
			$compare_functions[] = [self::class, '_related_getParentIndex'];
		}
		if (in_array('type', $parameters)) {
			$compare_functions[] = [self::class, '_related_getTypeSortIndex'];
		}
		if (in_array('index', $parameters)) {
			$compare_functions[] = [self::class, '_related_getSortIndex'];
		}


		usort($postlist, function ($a, $b) use ($compare_functions) {
			$id_a = az_wp::get_id($a);
			$id_b = az_wp::get_id($b);

			foreach ($compare_functions as $cmpf) {
				$index_a = 0;
				$index_b = 0;
				/* ------------------------------- for post A ------------------------------- */
				if (!empty($id_a)) {
					$cache_key = $cmpf[1] . '_' . $id_a;
					$index_a = az_cache::get(
						$cache_key,
						function () use ($cmpf, $a) {
							return call_user_func($cmpf, $a);
						}
					);
				} else {
					$index_a = call_user_func($cmpf, $a);
				}
				/* ------------------------------- for post B ------------------------------- */
				if (!empty($id_b)) {
					$cache_key = $cmpf[1] . '_' . $id_b;
					$index_b = az_cache::get(
						$cache_key,
						function () use ($cmpf, $b) {
							return call_user_func($cmpf, $b);
						}
					);
				} else {
					$index_b = call_user_func($cmpf, $a);
				}
				/* -------------------------------------------------------------------------- */
				$sorting = floatval($index_a) - floatval($index_b);
				if ($sorting != 0) return $sorting;
			}
			return $id_a - $id_b;
		});
		return $postlist;
	}

	static function _related_getTypeSortIndex(object $post): int
	{
		$sortIndexMap = array(
			"page" => 1,
			"product" => 2,
			"post" => 3,
			"product_doc" => 3,
			"project" => 5,
			"attachment" => 6
		);

		$found_post = az_wp::get_post($post);
		if (!empty($found_post)) $post = $found_post;
		if (is_object($post) && !empty($sortIndexMap[$post->post_type])) {
			$typeindex = ($sortIndexMap[$post->post_type]);
		}
		if (empty($typeindex)) {
			/**
			 * if no sorting by type is set, send the post to last
			 */
			return  max(array_values($sortIndexMap)) + 1;
		}
		return $typeindex;
	}


	/**
	 * Return sorting index of a post's parent
	 */
	static function _related_getParentIndex(object $post): int
	{
		$post = az_wp::get_post($post, 'any');

		if (empty($post) || $post->post_parent <= 0)
			return 0;

		return az_wp::get_meta_numeric_of($post->post_parent, 'paginator_pagenumber', 0);
	}

	/**
	 * Return sorting index of a given post.
	 * lower numbers are shown first
	 */
	static function _related_getSortIndex(object $post): int
	{
		$found_post = az_wp::get_post($post);
		if (!empty($found_post)) {
			$postindex = az_wp::get_meta_numeric_of($found_post, 'paginator_pagenumber');
		}
		if (empty($postindex)) {
			/**
			 * if no sorting value is set, use the default sorting method 
			 * similar types are sorted based on translate_post_type
			 */
			$postindex = strlen(az_i18n::get_actual_post_type($post));
		}
		return $postindex;
	}
}
