<?php

namespace Neat\Database\Test;

use Neat\Database\SQLQuery;
use PHPUnit\Framework\TestCase;

class SQLQueryTest extends TestCase
{
    use Factory;

    /**
     * Test getQuery
     */
    public function testGetQuery()
    {
        $sql   = "Test that getQuery() returns this.";
        $query = new SQLQuery($this->connection(), $sql);
        $this->assertSame($sql, $query->getQuery());
    }
}
