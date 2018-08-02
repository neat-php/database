<?php

namespace Neat\Database;

use Countable;
use Traversable;

class Schema implements Countable, Traversable
{
    const TYPE_TABLE = 'BASE TABLE';
    const TYPE_VIEW = 'VIEW';

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $name;

    /**
     * Table constructor
     *
     * @param Connection $connection
     * @param string     $name
     */
    public function __construct(Connection $connection, string $name)
    {
        $this->connection = $connection;
        $this->name       = $name;
    }

    /**
     * Count tables
     *
     * @return int
     */
    public function count()
    {
        return $this->connection->query("SELECT COUNT(1) FROM INFORMATION_SCHEMA.TABLES")->value();
    }

    /**
     * Get table
     *
     * @param string $name
     * @return Table
     */
    public function table($name)
    {
        return new Table($this->connection, $this->name . '.' . $name);
    }

    /**
     * Get tables
     *
     * @return Table[]
     */
    public function tables()
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * Get table iterator
     *
     * @return \Generator
     */
    public function getIterator()
    {
        foreach ($this->connection->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES")->values() as $name) {
            yield $name => $this->table($name);
        }
    }
}
