<?php

namespace AzUtils;

class az_jsonld
{
	public static function getItemList($items, $type = 'Products'): array
	{
		$product_schemas = [];
		foreach ($items as $pr) {
			$product_schemas[] = self::getItem($pr, $type);
		}
		return (array(
			'@context' => "https://schema.org",
			'itemListOrder' => "https://schema.org/ItemListOrderDescending",
			'itemListElement' => $product_schemas,
			"@type" => "ItemList",
			'name' => $type
		));
	}
	public static function getItem($item, string $type): array
	{
		if ($type == 'Products') return self::getProduct($item);
		return array(
			"@type" => $type,
		);
	}
	public static function getProduct($pr): array
	{
		assert(is_a($pr, 'WC_Product'), 'jsonld: invalid product');
		assert(function_exists('get_woocommerce_currency'), 'jsonld: WC is not installed');

		// return YoastSEO()->meta->for_post($pr->get_id())->schema; 
		$validuntil = (new DateTime('tomorrow'))->format(DateTime::ATOM);
		$currency = get_woocommerce_currency();
		$desc = $pr->get_description();
		if (empty($desc)) $desc = $pr->get_title();

		return array(
			"@type" => "Product",
			'image' => wp_get_attachment_url($pr->get_image_id()),
			'url' => $pr->get_permalink(),
			'name' => $pr->get_title(),
			'description' => $desc,
			'AggregateRating' =>
			array(
				"ratingValue" => max(1, $pr->get_average_rating() || 5),
				"ratingCount" => max(1, $pr->get_rating_count()),
				"bestRating" => 5,
				"worstRating" => 1
			),
			'offers' => array(
				"@type" => "Offer",
				"availability" =>
				$pr->is_in_stock() ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
				"price" => $pr->get_price(),
				"priceCurrency" => $currency,
				"priceValidUntil" => $validuntil,
				"url" => $pr->get_permalink(),
			),
		);
	}
}
