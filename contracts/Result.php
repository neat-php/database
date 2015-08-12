<?php namespace Phrodo\Database\Contract;

/**
 * Result interface
 */
interface Result
{

    /**
     * Call a closure for each row
     *
     * @param callable $closure
     * @return array
     */
    public function each(callable $closure);

    /**
     * Get all rows as array
     *
     * Moves the cursor to the end of the result
     *
     * @return array
     */
    public function rows();

    /**
     * Get a single row as array
     *
     * Moves the cursor to the next row
     *
     * @return array
     */
    public function row();

    /**
     * Get all values from one column
     *
     * Moves the cursor to the end of the result
     *
     * @param int|string $column
     * @return array
     */
    public function values($column = 0);

    /**
     * Get a single value from one column
     *
     * Moves the cursor to the next row
     *
     * @param int|string $column
     * @return mixed
     */
    public function value($column = 0);

}
