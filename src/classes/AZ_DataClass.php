<?php

namespace AzUtils\classes;

use AzUtils\az_wp;

class AZ_DataClass
{
	static $check_meta = [];

	private $__data = [];
	private $__options = [];
	protected   int|null $ID;

	static function init(array $check_meta)
	{
		static::$check_meta = $check_meta;
	}

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

	/**
	 * get a property (or Meta if its in valid meta list) 
	 * if value is not set use the setter function to set the value 
	 */
	function get(string $key, null|callable $setter = null)
	{
		if (isset($this->__data[$key]))
			return $this->__data[$key];
		if (in_array($key, static::$check_meta)) {
			$this->__data[$key] = $this->getMeta($key);
			return $this->__data[$key];
		}
		if (is_callable($setter)) {
			return $this->set($key, $setter());
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
		return az_wp::getMetaListOf($search, $key_list);
	}
	static function getMetaOf($search, string $key)
	{
		return az_wp::getMetaOf($search, $key);
	}
	static function getMetaBoolOf($search, string $key): bool
	{
		return az_wp::getMetaBoolOf($search, $key);
	}
	static function getMetaNumericOf($search, string $key, int $default = -1): int|float
	{
		return az_wp::getMetaNumericOf($search, $key, $default);
	}
}
