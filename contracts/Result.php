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
     * @return array
     */
    public function rows();

    /**
     * Get the first row as array
     *
     * @return array
     */
    public function row();

    /**
     * Get all values from one column
     *
     * @param int|string $column
     * @return array
     */
    public function values($column = 0);

    /**
     * Get the first value from one column
     *
     * @param int|string $column
     * @return mixed
     */
    public function value($column = 0);

}
