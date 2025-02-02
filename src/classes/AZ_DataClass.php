<?php

namespace AzUtils\classes;

class AZ_DataClass
{
	private $__data = [];
	private $__options = [];
	protected   int|null $ID;

	public function __construct(int|null $field_id = null)
	{
		$this->setID($field_id);
		$this->clear();
	}


	function setID(int|null $field_id)
	{
		$this->ID = $field_id;
	}


	function clear()
	{
		$this->__data[] = [];
		$this->__options[] = [];
	}
	function set(string $key, $value)
	{
		$this->__data[$key] = $value;
		return $value;
	}

	function get(string $key)
	{
		if (isset($this->__data[$key]))
			return $this->__data[$key];
		if (in_array($key, pcb_postutils::$check_meta)) {
			$this->__data[$key] = $this->getMeta($key);
			return $this->__data[$key];
		}
		return null;
	}

	function getBool($key): bool
	{
		return filter_var(
			$this->get($key),
			FILTER_VALIDATE_BOOL
		);
	}
	function getOptionBool($key): bool
	{
		return filter_var(
			$this->getOption($key),
			FILTER_VALIDATE_BOOL
		);
	}
	function getOption(string $key)
	{
		if (isset($this->__options[$key]))
			return $this->__options[$key];
		return null;
	}
	function setOption(string $key, $value)
	{
		$this->__options[$key] = $value;
		return $value;
	}

	/**
	 * tries to get a value, if value is not set use the setter function to set the value 
	 */
	function getWithSetter(string $key, callable $setter)
	{
		$val = $this->get($key);
		if (empty($val)) {
			$val = $this->set($key, $setter());
		}
		return $val;
	}

	function getMeta(string $key)
	{
		return AZ_DataClass::getMetaOf($this->ID, $key);
	}
	/**
	 * get value of a boolean meta field 
	 */
	function getMetaBool(string $key): bool
	{
		return AZ_DataClass::getMetaBoolOf($this->ID, $key);
	}
	/**
	 * get value of a numeric meta field 
	 */
	function getMetaNumeric(string $key, int $default = -1): int
	{
		return AZ_DataClass::getMetaNumericOf($this->ID, $key, $default);
	}

	static function getMetaListOf($search, array $key_list): array
	{
		if (is_a($search, 'WP_Post')) $search = $search->ID;
		$res = [];
		foreach ($key_list as $key) {
			$res[$key] = self::getMetaOf($search, $key);
		}
		return $res;
	}
	static function getMetaOf($search, string $key)
	{
		if (is_a($search, 'PCB_RequestField'))
			$search = $search->field;
		if (is_a($search, 'WC_Order_Item'))
			return $search->get_meta($key);
		if (is_a($search, 'WP_Post')) $search = $search->ID;
		if (is_object($search)) $search = $search->ID;
		return get_post_meta(
			$search,
			$key,
			true
		);
	}
	static function getMetaBoolOf($search, string $key): bool
	{
		$meta_value = self::getMetaOf(
			$search,
			$key
		);
		if (empty($meta_value)) return false;
		return filter_var(
			$meta_value,
			FILTER_VALIDATE_BOOL
		);
	}
	static function getMetaNumericOf($search, string $key, int $default = -1): int
	{
		$meta_value = self::getMetaOf(
			$search,
			$key
		);
		if (is_numeric($meta_value)) return intval($meta_value);
		return $default;
	}
}
