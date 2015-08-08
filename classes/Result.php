<?php namespace Phrodo\Database;

use IteratorAggregate;
use PDOStatement;
use PDO;

/**
 * Result class
 */
class Result implements Contract\Result, IteratorAggregate
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
     * @return array Results of each closure call
     */
    public function each(callable $closure)
    {
        $this->statement->fetchAll(PDO::FETCH_FUNC, $closure);
    }

    /**
     * Get all rows as array
     *
     * @return array
     */
    public function rows()
    {
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the first row as array
     *
     * @return array|false
     */
    public function row()
    {
        return $this->statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST);
    }

    /**
     * Get all values from one column
     *
     * @param int|string $column
     * @return array
     */
    public function values($column = 0)
    {
        return $this->statement->fetchAll(PDO::FETCH_COLUMN, $column);
    }

    /**
     * Get the first value from one column
     *
     * @param int|string $column
     * @return mixed|false
     */
    public function value($column = 0)
    {
        return $this->statement->fetchColumn($column);
    }

    /**
     * Get a (forward-only) iterator
     *
     * @return \Generator
     */
    public function getIterator()
    {
        $method = $this->statement->columnCount() > 1 ? 'row' : 'value';
        while ($item = $this->$method()) {
            yield $item;
        }
    }

}
