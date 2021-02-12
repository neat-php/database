<?php

namespace Neat\Database\Test;

use Neat\Database\ImmutableQueryBuilder;
use Neat\Database\MutableQueryBuilder;
use PHPUnit\Framework\TestCase;

class MutableQueryTest extends TestCase
{
    use Factory;
    use SQLHelper;

    public function provideMethods(): array
    {
        return [
            ['select'],
            ['select', 'id'],
            ['insert', 'users'],
            ['update', 'users'],
            ['upsert', 'users'],
            ['delete', 'users'],
            ['from', 'users'],
            ['from', 'users', 'u'],
            ['into', 'users'],
            ['into', 'users', 'u'],
            ['join', 'users', 'u', 'u', 'INNER JOIN'],
            ['innerJoin', 'users', 'u', 'u'],
            ['leftJoin', 'users', 'u', 'u'],
            ['rightJoin', 'users', 'u', 'u'],
            ['values', ['a', 'b']],
            ['set', ['a', 'b']],
            ['where', ['a' => 1, 'b' => 2]],
            ['where', "a = ? AND b = ?", 1, 2],
            ['groupBy', "id"],
            ['having', ['a' => 1, 'b' => 2]],
            ['having', "a = ? AND b = ?", 1, 2],
            ['orderBy', "id"],
            ['limit'],
            ['limit', 10],
            ['offset'],
            ['offset', 10],
        ];
    }

    /**
     * @dataProvider provideMethods
     * @param string $method
     * @param mixed  ...$arguments
     * @return void
     */
    public function testMutability(string $method, ...$arguments)
    {
        $query = $this->query('mutable');
        $this->assertInstanceOf(MutableQueryBuilder::class, $query);

        $this->assertSame($query, $query->$method(...$arguments));
    }

    /**
     * @dataProvider provideMethods
     * @param string $method
     * @param mixed  ...$arguments
     * @return void
     */
    public function testImmutability(string $method, ...$arguments)
    {
        $query = $this->query('immutable');
        $this->assertInstanceOf(ImmutableQueryBuilder::class, $query);
        $before = var_export($query, true);

        $result = $query->$method(...$arguments);
        $this->assertSame($before, var_export($query, true));
        $this->assertInstanceOf(ImmutableQueryBuilder::class, $result);
        $this->assertNotSame($query, $result);
    }
}
