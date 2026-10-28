<?php

namespace Test\Michel\PaperORM\Common;

use Michel\PaperORM\Tools\IDBuilder;
use Michel\UniTester\TestCase;

class IDBuilderTest extends TestCase
{

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    protected function execute(): void
    {
        foreach ([16, 32, 64, 128] as $length) {
            $token = IDBuilder::generate(sprintf('{TOKEN%s}', $length));
            $this->assertStringLength($token, $length);
            $this->assertTrue(ctype_xdigit($token));
        }
        $this->assertTrue(IDBuilder::generate('{TOKEN32}') !== IDBuilder::generate('{TOKEN32}'));

        $this->assertStrictEquals(1, preg_match('/^\d{2}:\d{2}:\d{2}$/', IDBuilder::generate('{HH}:{ii}:{ss}')));
        $this->assertStrictEquals('inv-' . date('Y') . '-', IDBuilder::generate('inv-{YYYY}-'));
        $this->assertStrictEquals('{yyyy}', IDBuilder::generate('{yyyy}'));
    }

}
