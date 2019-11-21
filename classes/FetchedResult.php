<?php

namespace Neat\Database;

use Countable;
use SeekableIterator;

/**
 * Fetched result class
 */
class FetchedResult implements Countable, SeekableIterator
{
    /** @var array */
    protected $rows;

    /** @var int */
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
     * Call a closure for each row
     *
     * @param callable $closure
     * @return array
     */
    public function each(callable $closure)
    {
        $results = [];
        foreach ($this->rows as $row) {
            $results[] = $closure(...array_values($row));
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
        return $this->rows;
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
        if (isset($this->rows[$this->cursor])) {
            return $this->rows[$this->cursor++];
        }

        return false;
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
            $column = $this->rows ? array_keys($this->rows[0])[$column] : '';
        }

        return array_map(function ($row) use ($column) {
            return $row[$column];
        }, $this->rows);
    }

    /**
     * Get a single value from one column
     *
     * Moves the cursor to the next row
     *
     * @param int|string $column
     * @return mixed|false
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
     * Count the number of rows
     *
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * Rewind the cursor to the first row
     */
    public function rewind()
    {
        $this->cursor = 0;
    }

    /**
     * Get the current row
     *
     * @return array
     */
    public function current()
    {
        return $this->rows[$this->cursor];
    }

    /**
     * Get the current cursor index
     */
    public function key()
    {
        return $this->cursor;
    }

    /**
     * Move the cursor to the next row
     */
    public function next()
    {
        $this->cursor++;
    }

    /**
     * Test if the cursor points to a valid row
     */
    public function valid()
    {
        return isset($this->rows[$this->cursor]);
    }

    /**
     * Move the cursor to a given row
     *
     * @param int $position
     */
    public function seek($position)
    {
        $this->cursor = $position;
    }
}
