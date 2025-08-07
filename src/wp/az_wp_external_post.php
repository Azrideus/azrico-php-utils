<?php

namespace AzUtils\wp;

trait az_wp_external_post
{


	/**
	 * get post link.
	 * works for both internal posts and external links such as youtube videos 
	 * @param [type] $post
	 */
	static function get_post_link(object $post): string
	{
		if ($post instanceof WC_Product)
			return $post->get_permalink();
		if (is_int($post))
			$itemid = $post->ID;
		if ($post instanceof \WP_Post)
			$itemid = $post->ID;

		if (empty($itemid))
			return '';
		if ($itemid >= 0)
			return get_permalink($itemid);
		if ($itemid < -2) //external post
			return $post->post_content;
		return '';
	}
	/**
	 * get post image link.
	 * works for internal posts and external links such as youtube videos 
	 * @param [type] $post
	 */
	static function getImageLink(object $post): string|false
	{
		$itemid = $post->ID;
		if ($itemid >= 0)
			return get_the_post_thumbnail_url($itemid);

		if ($itemid < -2) //external post
		{
			if (str_contains($post->post_name, 'youtube'))
				return 'https://img.youtube.com/vi/' . $post->post_excerpt . '/1.jpg';
		}
		return false;
	}
}
