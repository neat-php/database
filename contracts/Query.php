<?php namespace Phrodo\Database\Contract;

/**
 * Query builder interface
 */
interface Query extends Result
{

    /**
     * Select type
     */
    const TYPE_SELECT = 'SELECT';

    /**
     * Insert type
     */
    const TYPE_INSERT = 'INSERT';

    /**
     * Update type
     */
    const TYPE_UPDATE = 'UPDATE';

    /**
     * Delete type
     */
    const TYPE_DELETE = 'DELETE';

    /**
     * Select query
     *
     * @param array|string $select
     * @return $this
     */
    public function select($select = '*');

    /**
     * Insert query
     *
     * @param string $table (optional)
     * @param array  $data  (optional)
     * @return $this
     */
    public function insert($table = null, array $data = null);

    /**
     * Update query
     *
     * @param string       $table (optional)
     * @param array        $data  (optional)
     * @param array|string $where (optional)
     * @return $this
     */
    public function update($table = null, array $data = null, $where = null);

    /**
     * Delete query
     *
     * @param string       $table (optional)
     * @param array|string $where (optional)
     * @return $this
     */
    public function delete($table = null, $where = null);

    /**
     * Use table
     *
     * @param string $table
     * @param string $alias (optional)
     * @return $this
     */
    public function table($table, $alias = null);

    /**
     * From table
     *
     * @param string $table
     * @param string $alias (optional)
     * @return $this
     */
    public function from($table, $alias = null);

    /**
     * Into table
     *
     * @param string $table
     * @param string $alias (optional)
     * @return $this
     */
    public function into($table, $alias = null);

    /**
     * Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @param string $type
     * @return $this
     */
    public function join($table, $alias = null, $on = null, $type = "INNER JOIN");

    /**
     * LEFT OUTER Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @return $this
     */
    public function leftJoin($table, $alias = null, $on = null);

    /**
     * RIGHT OUTER Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @return $this
     */
    public function rightJoin($table, $alias = null, $on = null);

    /**
     * INNER Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @return $this
     */
    public function innerJoin($table, $alias = null, $on = null);

    /**
     * Data to set
     *
     * @param array $data
     * @return $this
     */
    public function set(array $data);

    /**
     * Where condition
     *
     * @param array|string $conditions
     * @param array        $parameters
     * @return $this
     */
    public function where($conditions, $parameters = null);

    /**
     * Group by column
     *
     * @param string $groupBy
     * @return $this
     */
    public function groupBy($groupBy);

    /**
     * Having condition
     *
     * @param $condition
     * @return $this;
     */
    public function having($condition);

    /**
     * Order by column
     *
     * @param string $orderBy
     * @return $this
     */
    public function orderBy($orderBy);

    /**
     * Limit number of results/items
     *
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit($limit, $offset = 0);

    /**
     * Fail hard and throw an exception when no rows are found or affected
     *
     * @param string $message
     * @return $this
     */
    public function orFail($message = "No rows found or affected");

    /**
     * No rows affected? Insert row instead
     *
     * @return $this
     */
    public function orInsert();

    /**
     * Get columns
     *
     * @return string
     */
    public function getSelect();

    /**
     * Get tables
     *
     * @return string
     */
    public function getTable();

    /**
     * Get from query part
     *
     * @return string
     */
    public function getFrom();

    /**
     * Get where query part
     *
     * @return string
     */
    public function getWhere();

    /**
     * Get group by query part
     *
     * @return string
     */
    public function getGroupBy();

    /**
     * Get having query part
     *
     * @return string
     */
    public function getHaving();

    /**
     * Get order by query part
     *
     * @return string
     */
    public function getOrderBy();

    /**
     * Get limit query part
     *
     * @return string
     */
    public function getLimit();

    /**
     * Get select query
     *
     * @return string
     */
    public function getSelectQuery();

    /**
     * Get insert query
     *
     * @return string
     */
    public function getInsertQuery();

    /**
     * Get update query
     *
     * @return string
     */
    public function getUpdateQuery();

    /**
     * Get delete query
     *
     * @return string
     */
    public function getDeleteQuery();

    /**
     * Get SQL Query
     *
     * @return string
     */
    public function getQuery();

    /**
     * Run a query and return the results
     *
     * @return Result
     */
    public function query();

    /**
     * Execute the query and return the number of rows affected
     *
     * @return int
     */
    public function execute();

}
