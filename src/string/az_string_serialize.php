<?php

namespace AzUtils\string;

trait az_string_serialize
{
	/**
	 * unserialize given input if it is serialized
	 *
	 * @param [type] $input
	 * @return void
	 */
	static function unser($input)
	{
		if (is_string($input) && str_starts_with($input, 'a:')) {
			return unserialize($input);
		}
		return $input;
	}
}
