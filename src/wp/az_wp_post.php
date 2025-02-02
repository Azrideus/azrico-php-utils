<?php

namespace AzUtils\wp;

trait az_wp_post
{


	/**
	 * check if we are in a blog based page 
	 */
	static function is_blog(): bool
	{
		$uri_parts = array_map('strtolower', array_filter(explode('/', $_SERVER['REQUEST_URI'])));

		foreach ($uri_parts as $part) {
			if ($part == 'blog') return true;
		}
		return false;
	}



	/**
	 * get id of given WP_Post or WC_Product
	 *
	 * @param [type] $input
	 * @return integer|null
	 */
	static function getId($input): int|null
	{
		if (is_a($input, 'WP_Post')) return $input->ID;
		if (is_a($input, 'WC_Product')) return $input->get_id();
		return null;
	}

	/**
	 * check if post type of input matches one of the allowedTypes
	 *
	 * @param [type] $input
	 * @param array|string $allowedTypes 
	 */
	static function postTypeMatches(object $input, array|string $allowedTypes)
	{
		if (is_object($input) && property_exists($input, 'post_type'))
			$input = $input->post_type;
		else if (is_a($input, 'WC_Product'))
			$input = 'product';
		else if (is_a($input, 'WP_Post'))
			$input = 'post';


		$allowedTypes = (array)$allowedTypes;
		if (sizeof($allowedTypes) === 0) return false;
		if (isset($allowedTypes[0]) && $allowedTypes[0] === 'any') return true;
		return in_array(strval($input), $allowedTypes);
	}
}
