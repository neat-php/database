<?php namespace Phrodo\Database;

use Some\Database\Result as ResultContract;
use SeekableIterator;
use Countable;
use Iterator;

/**
 * Fetched result class
 */
class FetchedResult implements ResultContract, Countable, Iterator, SeekableIterator
{
    /**
     * Rows
     *
     * @var array
     */
    protected $rows;

    /**
     * Row cursor
     *
     * @var int
     */
    protected $cursor = 0;

    /**
     * Constructor
     *
     * @param array $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * @inheritdoc
     */
    public function each(callable $closure)
    {
        $results = [];
        foreach ($this->rows as $row) {
            $results[] = call_user_func_array($closure, $row);
        }

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function rows()
    {
        return $this->rows;
    }

    /**
     * @inheritdoc
     */
    public function row()
    {
        if (isset($this->rows[$this->cursor])) {
            return $this->rows[$this->cursor++];
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function values($column = 0)
    {
        if (is_int($column)) {
            $column = $this->rows ? array_keys($this->rows[0])[$column] : '';
        }

        return array_map(function ($row) use ($column) {
            return $row[$column];
        }, $this->rows);
    }

    /**
     * @inheritdoc
     */
    public function value($column = 0)
    {
        $row = $this->row();
        if ($row) {
            return is_int($column) ? array_values($row)[$column] : $row[$column];
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->cursor = 0;
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        $row = $this->rows[$this->cursor];
        if (count($row) > 1) {
            return $row;
        }

        return array_values($row)[0];
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->cursor;
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->cursor++;
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return isset($this->rows[$this->cursor]);
    }

    /**
     * @inheritdoc
     */
    public function seek($position)
    {
        $this->cursor = $position;
    }
}
