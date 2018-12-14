<?php

namespace Neat\Database\Test;

use Neat\Database\SQLQuery;
use PHPUnit\Framework\TestCase;

class SQLQueryTest extends TestCase
{
    /**
     * @var Factory
     */
    protected $create;

    /**
     * Setup factory
     */
    protected function setup()
    {
        $this->create = new Factory($this);
    }

    /**
     * Test getQuery
     */
    public function testGetQuery()
    {
        $sql   = "Test that getQuery() returns this.";
        $query = new SQLQuery($this->create->connection(), $sql);
        $this->assertSame($sql, $query->getQuery());
    }
}
