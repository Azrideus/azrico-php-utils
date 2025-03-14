<?php

declare(strict_types=1);

use AzUtils\az_string;
use PHPUnit\Framework\TestCase;

final class StringTest extends TestCase
{
	public function testEq(): void
	{

		$this->assertTrue(az_string::eq("a", "a"));
	}
}
