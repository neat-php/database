<?php namespace Phrodo\Database\Contract;

/**
 * Database connection interface
 */
interface Connection
{

    /**
     * Quote a value (protecting against SQL injection)
     *
     * @param string|null $value
     * @return string
     */
    public function quote($value);

    /**
     * Merge data into an SQL query with placeholders
     *
     * @param string $query
     * @param array  $data
     * @return string
     */
    public function merge($query, array $data);

    /**
     * Run a query and return the result
     *
     * @param string $query
     * @param mixed  ... $data
     * @return Result
     */
    public function query($query);

    /**
     * Execute a query and return the number of rows affected
     *
     * @param string $query
     * @param mixed  ... $data
     * @return int
     */
    public function execute($query);

    /**
     * Select data from the database
     *
     * @param array|string $expression (optional, defaults to *)
     * @return Query
     */
    public function select($expression = '*');

    /**
     * Insert data into a table
     *
     * When all parameters are specified, the insert query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param string $table
     * @param array  $data  (optional)
     * @return Query|int
     */
    public function insert($table, array $data = null);

    /**
     * Update data in a table
     *
     * When all parameters are specified, the update query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param string       $table
     * @param array        $data  (optional)
     * @param array|string $where (optional)
     * @return Query|int
     */
    public function update($table, array $data = null, $where = null);

    /**
     * Delete from a table
     *
     * When all parameters are specified, the delete query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param string       $table
     * @param array|string $where (optional)
     * @return Query|int
     */
    public function delete($table, $where = null);

    /**
     * Run a closure wrapping it in a transaction
     *
     * @param callable $closure
     * @return Transaction|mixed
     */
    public function transaction(callable $closure = null);

}
