<?php

namespace Neat\Database\Test;

use Neat\Database\Connection;
use Neat\Database\Query\NotEquals;
use PHPUnit\Framework\TestCase;

class NotEqualsTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    public function setUp()
    {
        $this->connection = (new Factory($this))->connection();
    }

    public function test__construct()
    {
        $this->expectException(\TypeError::class);
        new NotEquals([]);
    }

    public function getConditionProvider()
    {
        return [
            [true, " != '1'"],
            [false, " != '0'"],
            [1, " != '1'"],
            ['1', " != '1'"],
            [null, " IS NOT NULL"],
            [new \DateTime('2019-10-01 00:00:00'), " != '2019-10-01 00:00:00'"],
            [new \DateTimeImmutable('2019-10-01 00:00:00'), " != '2019-10-01 00:00:00'"],
        ];
    }

    /**
     * @dataProvider getConditionProvider
     * @param null|bool|int|string|\DateTime $input
     * @param string $expected
     */
    public function testGetCondition($input, string $expected)
    {
        $notEquals = new NotEquals($input);
        $this->assertSame($expected, $notEquals->getCondition($this->connection));
    }
}
