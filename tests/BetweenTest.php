<?php

namespace Neat\Database\Test;

use Neat\Database\Connection;
use Neat\Database\Query\Between;
use PHPUnit\Framework\TestCase;

class BetweenTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    public function setUp()
    {
        $this->connection = (new Factory($this))->connection();
    }

    public function testConstruct()
    {
        $this->expectException(\TypeError::class);
        new Between("", "");
    }

    public function getConditionProvider()
    {
        return [
            [0, 5, " BETWEEN '0' AND '5'"],
            [new \DateTime('2018-05-01 00:00:00'), new \DateTime('2018-05-31 23:59:59'), " BETWEEN '2018-05-01 00:00:00' AND '2018-05-31 23:59:59'"],
            [new \DateTimeImmutable('2018-05-01 00:00:00'), new \DateTimeImmutable('2018-05-31 23:59:59'), " BETWEEN '2018-05-01 00:00:00' AND '2018-05-31 23:59:59'"],
        ];
    }

    /**
     * @dataProvider getConditionProvider
     * @param int|\DateTimeInterface $min
     * @param int|\DateTimeInterface $max
     * @param string $expected
     */
    public function testGetCondition($min, $max, string $expected)
    {
        $between = new Between($min, $max);
        $this->assertSame($expected, $between->getCondition($this->connection));
    }

    public function testDateTime()
    {
        $min      = new \DateTime('2018-05-01 00:00:00');
        $max      = new \DateTime('2018-05-31 23:59:59');
        $expected = " BETWEEN '2018-05-01 00:00:00' AND '2018-05-31 23:59:59'";

        $between = new Between($min, $max);
        $this->assertSame($expected, $between->getCondition($this->connection));
    }

    public function testInt()
    {
        $min      = 0;
        $max      = 5;
        $expected = " BETWEEN '0' AND '5'";

        $between = new Between($min, $max);
        $this->assertSame($expected, $between->getCondition($this->connection));
    }
}
