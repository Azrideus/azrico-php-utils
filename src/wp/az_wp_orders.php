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
		return 'wc-' . static::get_wc_order_status_no_prefix($order);
	}
	/**
	 * get the order status of the given order and remove the prefix 'wc-'
	 */
	static function get_wc_order_status_no_prefix(\WC_Order|string $order)
	{
		if (is_string($order)) $order_status = $order;
		else if (\is_object($order)) $order_status = $order->get_status();

		if (empty($order_status)) return null;
		if (str_starts_with($order_status, 'wc-')) return substr($order_status, 3);
		return   $order_status;
	}
	/**
	 * check if the given order status is equal to the given order status 
	 */
	static function wc_status_equals(\WC_Order|string $order1, \WC_Order|string $order2): bool
	{
		if ($order1 === $order2) return true;
		$order_status1 = static::get_wc_order_status_no_prefix($order1);
		$order_status2 = static::get_wc_order_status_no_prefix($order2);
		return az_string::eq($order_status1, $order_status2);
	}
	/**
	 * check if the given status list contains the given status 
	 */
	static function wc_status_contains(array $status_list, \WC_Order|string $status): bool
	{
		/**
		 * quick check if the status is in the list
		 */
		if (in_array($status, $status_list)) return true;
		/**
		 * check if the status is in the list with wc- prefix
		 */
		$status_prefixed = static::get_wc_order_status_no_prefix($status);
		if (in_array($status_prefixed, $status_list)) return true;

		foreach ($status_list as $s) {
			$check_prefixed = static::get_wc_order_status_no_prefix($s);
			if (az_string::eq($check_prefixed, $status_prefixed)) return true;
		}
		return false;
	}

	/**
	 * using `wc_get_order_statuses` search for the given status in the list of order statuses 
	 */
	static function get_wc_order_status(array $status_list, \WC_Order|string $status): string|null
	{
		$status_list = wc_get_order_statuses();
		$status_prefixed = static::get_wc_order_status_no_prefix($status);
		foreach ($status_list as $s) {
			$check_prefixed = static::get_wc_order_status_no_prefix($s);
			if (az_string::eq($check_prefixed, $status_prefixed)) return $s;
		}
		return null;
	}
}
