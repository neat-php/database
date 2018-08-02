<?php

namespace Neat\Database;

class Table
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $name;

    /**
     * Table constructor.
     *
     * @param Connection $connection
     * @param string     $name
     */
    public function __construct(Connection $connection, string $name)
    {
        $this->connection = $connection;
        $this->name       = $name;
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Count rows in table
     *
     * @return int
     */
    public function count()
    {
        return $this->connection
            ->select('COUNT(1)')
            ->from($this->name)
            ->query()
            ->value();
    }

    /**
     * Select from table
     *
     * @param string $alias (optional)
     * @return Query
     */
    public function select($alias = null)
    {
        return $this->connection
            ->select(($alias ?? $this->name) . '.*')
            ->from($this->name, $alias);
    }

    /**
     * Insert data into table
     *
     * When all parameters are specified, the insert query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param array $data (optional)
     * @return Query|int
     */
    public function insert(array $data = null)
    {
        return $this->connection->insert($this->name, $data);
    }

    /**
     * Update data in table
     *
     * When all parameters are specified, the update query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param array        $data  (optional)
     * @param array|string $where (optional)
     * @return Query|int
     */
    public function update(array $data = null, $where = null)
    {
        return $this->connection->update($this->name, $data, $where);
    }

    /**
     * Delete from a table
     *
     * When all parameters are specified, the delete query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param array|string $where (optional)
     * @return Query|int
     */
    public function delete($where = null)
    {
        return $this->connection->delete($this->name, $where);
    }
}
