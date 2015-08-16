<?php namespace Phrodo\Database\Test;

use Phrodo\Database\FetchedResult;

class FetchedResultTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Rows
     *
     * @var array
     */
    private $rows = [
        ['id' => '3', 'username' => 'bob'],
        ['id' => '2', 'username' => 'jane'],
        ['id' => '1', 'username' => 'john'],
    ];

    public function testRead()
    {
        $query = function () {
            return new FetchedResult($this->rows);
        };

        $this->assertEquals($this->rows, $query()->rows());
        $this->assertEquals(['id' => '3', 'username' => 'bob'], $query()->row());
        $this->assertEquals([3, 2, 1], $query()->values());
        $this->assertEquals(['bob', 'jane', 'john'], $query()->values(1));
        $this->assertEquals(3, $query()->value());
        $this->assertEquals('bob', $query()->value(1));
    }

    public function testTraverse()
    {
        $result = new FetchedResult($this->rows);
        $this->assertEquals(['id' => '3', 'username' => 'bob'], $result->row());
        $this->assertEquals(['id' => '2', 'username' => 'jane'], $result->row());
        $this->assertEquals(['id' => '1', 'username' => 'john'], $result->row());
        $this->assertFalse($result->row());

        $result = new FetchedResult([['username' => 'bob'], ['username' => 'jane'], ['username' => 'john']]);
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

        $expected = $this->rows;
        foreach (new FetchedResult($this->rows) as $username) {
            $this->assertEquals(array_shift($expected), $username);
        }

        $expected = ['bob', 'jane', 'john'];
        $result = new FetchedResult([['username' => 'bob'], ['username' => 'jane'], ['username' => 'john']]);
        foreach ($result as $username) {
            $this->assertEquals(array_shift($expected), $username);
        }

        $expected = $this->rows;
        $result = new FetchedResult($this->rows);
        $result->each(function ($id, $username) use (&$expected) {
            $this->assertEquals(array_shift($expected), ['id' => $id, 'username' => $username]);
        });
    }

    public function testCount()
    {
        $result = new FetchedResult($this->rows);

        $this->assertEquals(3, count($result));
    }

}
