<?php namespace Phrodo\Database;

use Phrodo\Contract\Database\Result as ResultContract;

/**
 * Result class
 */
class Result implements ResultContract
{

    /**
     * PDO Statement to fetch results from
     *
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * Constructor
     *
     * @param \PDOStatement $statement
     */
    public function __construct(\PDOStatement $statement)
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
        $this->statement->fetchAll(\PDO::FETCH_FUNC, $closure);
    }

    /**
     * Get all rows as array
     *
     * @return array
     */
    public function rows()
    {
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get the first row as array
     *
     * @return array
     */
    public function row()
    {
        return $this->statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_FIRST);
    }

    /**
     * Get all values from one column
     *
     * @param int|string $column
     * @return array
     */
    public function values($column = 0)
    {
        return $this->statement->fetchAll(\PDO::FETCH_COLUMN, $column);
    }

    /**
     * Get the first value from one column
     *
     * @param int|string $column
     * @return mixed
     */
    public function value($column = 0)
    {
        return $this->statement->fetch(\PDO::FETCH_COLUMN, $column);
    }

    /**
     * Count number of rows
     *
     * @return int
     */
    public function count()
    {
        return $this->statement->rowCount();
    }

    /**
     * Get a (forward-only) iterator
     *
     * @return \Generator
     */
    public function getIterator()
    {
        $method = $this->statement->columnCount() > 1 ? 'row' : 'field';
        while ($item = $this->$method) {
            yield $item;
        }
    }

}
