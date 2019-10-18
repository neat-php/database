<?php

namespace Neat\Database\Test;

use Neat\Database\Connection;
use Neat\Database\Query\In;
use PHPUnit\Framework\TestCase;

class InTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    public function setUp()
    {
        $this->connection = (new Factory($this))->connection();
    }

    /**
     * Minify SQL query by removing unused whitespace
     *
     * @param string $query
     * @return string
     */
    protected function minifySQL($query)
    {
        $replace = [
            '|\s+|m'     => ' ',
            '|\s*,\s*|m' => ',',
            '|\s*=\s*|m' => '=',
        ];

        return preg_replace(array_keys($replace), $replace, $query);
    }

    /**
     * Assert SQL matches expectation
     *
     * Normalizes whitespace to make the tests less fragile
     *
     * @param string $expected
     * @param string $actual
     */
    protected function assertSQL($expected, $actual)
    {
        $this->assertEquals(
            $this->minifySQL($expected),
            $this->minifySQL($actual)
        );
    }

    public function testConstruct()
    {
        $this->expectException(\TypeError::class);
        new In(1);
    }

    public function testString()
    {
        $string   = '1, 2, 3';
        $expected = ' IN (1, 2, 3)';

        $in = new In($string);
        $this->assertSQL($expected, $in->getCondition($this->connection));
    }

    public function testArray()
    {
        $array    = [null, 1, 2];
        $expected = " IN (NULL,'1','2')";

        $in = new In($array);
        $this->assertSQL($expected, $in->getCondition($this->connection));
    }

    public function testQuery()
    {
        $query    = $this->connection->select('id')->from('users');
        $expected = ' IN (SELECT id FROM `users`)';

        $in = new In($query);
        $this->assertSQL($expected, $in->getCondition($this->connection));
    }
}
