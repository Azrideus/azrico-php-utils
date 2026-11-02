<?php

declare(strict_types=1);

use AzUtils\az_assets;
use PHPUnit\Framework\TestCase;

final class AssetsTest extends TestCase
{
	public function testCompressCss(): void
	{
		$css = "/* --- Header --- */\n.a  >  .b {\n\tcolor : red !important;\n\twidth: calc(100% - 2px);\n}\n\n/* multi\n line */ .c { margin: 0 auto; }";
		$this->assertSame(
			".a > .b{color:red!important;width:calc(100% - 2px)}.c{margin:0 auto}",
			az_assets::compress_css($css)
		);
	}

	public function testCompressCssKeepsDescendantPseudoSelector(): void
	{
		$css = ".a :hover { color : red; }\n@media (min-width: 600px) { .b :is(.c) { margin : 0; } }";
		$this->assertSame(
			".a :hover{color:red}@media (min-width:600px){.b :is(.c){margin:0}}",
			az_assets::compress_css($css)
		);
	}
}
