<?php

namespace AzUtils\wp;

use AzUtils\az_wp;

use function Avifinfo\read;

trait az_wp_external_post
{


	/**
	 * get post link.
	 * works for both internal posts and external links such as youtube videos 
	 * @param [type] $post
	 */
	static function get_post_link(object $post): string
	{
		if ($post instanceof \WC_Product)
			return $post->get_permalink();
		if (is_int($post))
			$post = az_wp::get_post($post);

		if (
			is_object($post)
			&& property_exists($post, 'post_type')

		) {
			$type = $post->post_type;
			if (
				in_array($type, ['external', 'youtube', 'github', 'external_post', 'external_link'])
				&& property_exists($post, 'post_content')
			) {
				/**
				 * For external posts, their link is the post content.
				 */
				return $post->post_content;
			}
		}

		if ($post instanceof \WP_Post) {
			return get_permalink($post->ID);
		}
		return '';
	}
	/**
	 * get post image link.
	 * works for internal posts and external links such as youtube videos 
	 * @param [type] $post
	 */
	static function get_post_image_link(object $post): string|false
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
