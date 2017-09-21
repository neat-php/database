<?php namespace Phrodo\Database;

use IteratorAggregate;
use PDOStatement;
use PDO;

/**
 * Result class
 */
class Result implements IteratorAggregate
{
    /**
     * PDO Statement to fetch results from
     *
     * @var PDOStatement
     */
    protected $statement;

    /**
     * Constructor
     *
     * @param PDOStatement $statement
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * Call a closure for each row
     *
     * @param callable $closure
     * @return array
     */
    public function each(callable $closure)
    {
        $results = [];
        while ($row = $this->statement->fetch(PDO::FETCH_NUM)) {
            $results[] = $closure(...$row);
        }

        return $results;
    }

    /**
     * Get all rows as arrays
     *
     * Moves the cursor to the end of the result
     *
     * @return array
     */
    public function rows()
    {
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single row as array
     *
     * Moves the cursor to the next row
     *
     * @return array|false
     */
    public function row()
    {
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all values from one column
     *
     * Moves the cursor to the end of the result
     *
     * @param int|string $column
     * @return array
     */
    public function values($column = 0)
    {
        if (is_int($column)) {
            return $this->statement->fetchAll(PDO::FETCH_COLUMN, $column);
        }

        return array_map(function ($row) use ($column) {
            return $row[$column];
        }, $this->rows());
    }

    /**
     * Get a single value from one column
     *
     * Moves the cursor to the next row
     *
     * @param int|string $column
     * @return mixed|false Value or false when not found
     */
    public function value($column = 0)
    {
        if (is_int($column)) {
            return $this->statement->fetchColumn($column);
        }

        return $this->row()[$column];
    }

    /**
     * Get a generator (used for traversing trough the rows)
     *
     * @return \Generator|array[]
     */
    public function getIterator()
    {
        while ($row = $this->row()) {
            yield $row;
        }
    }
}
