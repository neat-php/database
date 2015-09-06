<?php namespace Phrodo\Database;

use Some\Database\Result as ResultContract;
use IteratorAggregate;
use PDOStatement;
use PDO;

/**
 * Result class
 */
class Result implements ResultContract, IteratorAggregate
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
        $results = [];
        while ($row = $this->statement->fetch(PDO::FETCH_NUM)) {
            $results[] = call_user_func_array($closure, $row);
        }

        return $results;
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
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function value($column = 0)
    {
        if (is_int($column)) {
            return $this->statement->fetchColumn($column);
        }

        return $this->row()[$column];
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
