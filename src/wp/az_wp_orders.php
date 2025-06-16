<?php

namespace AzUtils\wp;

use AzUtils\az_string;

trait az_wp_orders
{

	/**
	 * get the order status of the given order and prefix it with 'wc-'
	 */
	static function get_wc_order_status_prefixed(\WC_Order|string $order)
	{
		return 'wc-' . static::get_wc_order_status_unprefixed($order);
	}
	/**
	 * get the order status of the given order and remove the prefix 'wc-'
	 */
	static function get_wc_order_status_unprefixed(\WC_Order|string $order)
	{
		if (is_string($order)) $order_status = $order;
		else if ($order instanceof \WC_Order) $order_status = $order->get_status();

		if (empty($order_status)) return null;
		if (str_starts_with($order_status, 'wc-')) return substr($order_status, 3);
		return $order_status;
	}
	/**
	 * check if the given order status is equal to the given order status 
	 */
	static function wc_status_equals(\WC_Order|string $order1, \WC_Order|string $order2): bool
	{
		if ($order1 === $order2) return true;
		$order_status1 = static::get_wc_order_status_unprefixed($order1);
		$order_status2 = static::get_wc_order_status_unprefixed($order2);
		return az_string::eq($order_status1, $order_status2);
	}
	/**
	 * check if the given status list contains the given status 
	 */
	static function wc_status_contains(\WC_Order|string $status, array $status_list): bool
	{
		$s = static::get_wc_order_status($status, $status_list);
		return !empty($s);
	}

	/**
	 * using `wc_get_order_statuses` search for the given status in the list of order statuses 
	 */
	static function get_wc_order_status(\WC_Order|string $status, array $status_list = []): string|null
	{
		if (empty($status_list)) $status_list = wc_get_order_statuses();

		/**
		 * Quick Check 1
		 */
		if (in_array($status, array_keys($status_list))) return $status;

		$status_no_prefix = static::get_wc_order_status_unprefixed($status);

		/**
		 * Quick Check 2
		 */
		if (in_array($status_no_prefix, array_keys($status_list))) return $status_no_prefix;

		foreach ($status_list as $key => $value) {
			if (
				$key == $status
				|| $key == $status_no_prefix
				|| $value == $status
				|| $value == $status_no_prefix
				|| az_string::eq(static::get_wc_order_status_unprefixed($key), $status_no_prefix)
				|| az_string::eq(static::get_wc_order_status_unprefixed($value), $status_no_prefix)
			) return $key;
		}
		return null;
	}
}
