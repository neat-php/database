<?php

namespace Neat\Database\Test;

use Neat\Database\Connection;
use Neat\Database\FetchedResult;
use Neat\Database\ImmutableQueryBuilder;
use Neat\Database\Query;
use Neat\Database\QueryInterface;
use Neat\Database\SQLQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueryTraitTest extends TestCase
{
    use SQLHelper;

    /**
     * @return MockObject|Connection
     */
    private function connection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['query', 'fetch', 'execute'])
            ->getMock();
    }

    /**
     * @return array
     */
    public function provideQuery(): array
    {
        return array_merge(...array_map(function (string $method) {
            return [
                [(new Query($connection = $this->connection()))->select('*')->from('users'), $connection, $method],
                [(new ImmutableQueryBuilder($connection = $this->connection()))->select('*')->from('users'), $connection, $method],
                [new SQLQuery($connection = $this->connection(), "SELECT * FROM `users`"), $connection, $method],
            ];
        }, ['query', 'fetch', 'execute']));
    }

    /**
     * @dataProvider provideQuery
     * @param QueryInterface        $query
     * @param Connection|MockObject $connection
     * @param string                $method
     */
    public function testQuery(QueryInterface $query, $connection, string $method)
    {
        $connection->expects($this->once())
            ->method($method)
            ->with($this->sql("SELECT * FROM `users`"))
            ->willReturn($result = new FetchedResult([['id' => 1, 'username' => 'john']]));

        $this->assertSame($result, $query->{$method}());
    }
}
