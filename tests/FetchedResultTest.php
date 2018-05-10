<?php

namespace Neat\Database\Test;

use Neat\Database\FetchedResult;
use PHPUnit\Framework\TestCase;

class FetchedResultTest extends TestCase
{
    /**
     * Rows
     *
     * @var array
     */
    protected $rows = [
        ['id' => '3', 'username' => 'bob'],
        ['id' => '2', 'username' => 'jane'],
        ['id' => '1', 'username' => 'john'],
    ];

    /**
     * Fetch fake rows into a fetched result
     *
     * @param array $rows (optional)
     * @return FetchedResult
     */
    protected function fetch($rows = null)
    {
        if (!$rows) {
            $rows = $this->rows;
        }

        return new FetchedResult($rows);
    }

    /**
     * Test read fetched result
     */
    public function testRead()
    {
        $this->assertEquals($this->rows, $this->fetch()->rows());
        $this->assertEquals(['id' => '3', 'username' => 'bob'], $this->fetch()->row());
        $this->assertEquals([3, 2, 1], $this->fetch()->values());
        $this->assertEquals(['bob', 'jane', 'john'], $this->fetch()->values(1));
        $this->assertEquals([3, 2, 1], $this->fetch()->values('id'));
        $this->assertEquals(['bob', 'jane', 'john'], $this->fetch()->values('username'));
        $this->assertEquals(3, $this->fetch()->value());
        $this->assertEquals('bob', $this->fetch()->value(1));
        $this->assertEquals(3, $this->fetch()->value('id'));
        $this->assertEquals('bob', $this->fetch()->value('username'));
    }

    /**
     * Test traversing fetched result
     */
    public function testTraverse()
    {
        $result = $this->fetch();
        $this->assertEquals(['id' => '3', 'username' => 'bob'], $result->row());
        $this->assertEquals(['id' => '2', 'username' => 'jane'], $result->row());
        $this->assertEquals(['id' => '1', 'username' => 'john'], $result->row());
        $this->assertFalse($result->row());

        $result = $this->fetch([
            ['username' => 'bob'],
            ['username' => 'jane'],
            ['username' => 'john']
        ]);
        $this->assertEquals(0, $result->key());
        $this->assertTrue($result->valid());
        $this->assertEquals('bob', $result->value());
        $this->assertEquals(1, $result->key());
        $this->assertEquals('jane', $result->value());
        $this->assertEquals(2, $result->key());
        $this->assertEquals('john', $result->value());
        $this->assertEquals(3, $result->key());
        $this->assertFalse($result->value());
        $this->assertFalse($result->valid());
        $result->seek(1);
        $this->assertTrue($result->valid());
        $this->assertEquals(1, $result->key());
        $this->assertEquals('jane', $result->value());
        $result->rewind();
        $this->assertTrue($result->valid());
        $this->assertEquals(0, $result->key());
        $this->assertEquals('bob', $result->value());

        $expected = $this->rows;
        foreach ($this->fetch() as $username) {
            $this->assertEquals(array_shift($expected), $username);
        }

        $expected = [
            ['username' => 'bob'],
            ['username' => 'jane'],
            ['username' => 'john']
        ];

        $result = $this->fetch($expected);
        foreach ($result as $username) {
            $this->assertEquals(array_shift($expected), $username);
        }

        $expected   = $this->rows;
        $result     = $this->fetch($this->rows);
        $eachResult = $result->each(function ($id, $username) use (&$expected) {
            $this->assertEquals(array_shift($expected), ['id' => $id, 'username' => $username]);

            return "$id:$username";
        });
        $this->assertEquals(['3:bob', '2:jane', '1:john'], $eachResult);
    }

    /**
     * Test count fetched result
     */
    public function testCount()
    {
        $this->assertEquals(3, count($this->fetch()));
    }
}
