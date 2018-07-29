<?php

namespace Neat\Database\Test;

use Neat\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;

class QueryExceptionTest extends TestCase
{
    /**
     * @var Factory
     */
    private $create;

    /**
     * Setup factory
     */
    public function setup()
    {
        $this->create = new Factory($this);
    }

    /**
     * Test new
     */
    public function testNew()
    {
        $pdoException = new PDOException('Error message', 0);
        $pdoException->errorInfo = [
            'HY000',
            'Driver code',
            'Driver message',
        ];

        $queryException = new QueryException($pdoException, 'SQL Query');

        $this->assertSame('Error message', $queryException->getMessage());
        $this->assertSame('SQL Query', $queryException->query());
        $this->assertSame('HY000', $queryException->state());
        $this->assertSame('Driver code', $queryException->driverCode());
        $this->assertSame('Driver message', $queryException->driverMessage());
        $this->assertSame($pdoException, $queryException->getPrevious());

    }

    /**
     * Provide trigger methods
     *
     * @return array
     */
    public function provideTriggerMethods()
    {
        return [
            ['query'],
            ['fetch'],
            ['execute'],
        ];
    }

    /**
     * Test trigger query exception
     *
     * @dataProvider provideTriggerMethods
     * @param string $method
     */
    public function testTrigger($method)
    {
        $connection = $this->create->connection();

        try {
            $connection->$method('INVALID SQL');
        }
        /** @noinspection PhpRedundantCatchClauseInspection */
        catch (QueryException $e) {
            $this->assertEquals('INVALID SQL', $e->query());
            $this->assertEquals(0, $e->getCode());
            $this->assertContains('syntax error', $e->getMessage());

            return;
        }

        $this->fail('QueryException expected');
    }
}
