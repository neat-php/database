<?php

namespace Neat\Database;

/**
 * SQL query class
 */
class SQLQuery implements QueryInterface
{
    use QueryTrait;

    /** @var Connection */
    protected $connection;

    /** @var string */
    protected $query;

    /**
     * SQLQuery constructor
     *
     * @param Connection $connection
     * @param string     $sql
     */
    public function __construct(Connection $connection, string $sql)
    {
        $this->connection = $connection;
        $this->query      = $sql;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return Connection
     */
    protected function connection(): Connection
    {
        return $this->connection;
    }
}
