<?php

namespace Neat\Database;

class SQLQuery implements QueryInterface
{
    use QueryTrait;

    /** @var Connection */
    protected $connection;

    /** @var string */
    protected $query;

    public function __construct(Connection $connection, string $sql)
    {
        $this->connection = $connection;
        $this->query      = $sql;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    protected function connection(): Connection
    {
        return $this->connection;
    }
}
