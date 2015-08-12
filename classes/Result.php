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
     * @inheritdoc
     */
    public function each(callable $closure)
    {
        $this->statement->fetchAll(PDO::FETCH_FUNC, $closure);
    }

    /**
     * @inheritdoc
     */
    public function rows()
    {
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @inheritdoc
     */
    public function row()
    {
        return $this->statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST);
    }

    /**
     * @inheritdoc
     */
    public function values($column = 0)
    {
        return $this->statement->fetchAll(PDO::FETCH_COLUMN, $column);
    }

    /**
     * @inheritdoc
     */
    public function value($column = 0)
    {
        return $this->statement->fetchColumn($column);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        $method = $this->statement->columnCount() > 1 ? 'row' : 'value';
        while ($item = $this->$method()) {
            yield $item;
        }
    }

}
