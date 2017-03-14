<?php namespace Phrodo\Database;

/**
 * Query builder class
 */
class Query
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
     * Connection
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Type
     *
     * @var string
     */
    protected $type;

    /**
     * Select expressions
     *
     * @var array
     */
    protected $expressions = [];

    /**
     * Tables by alias
     *
     * @var array
     */
    protected $tables = [];

    /**
     * Joins by alias
     *
     * @var array
     */
    protected $joins = [];

    /**
     * Data to insert or update
     *
     * @var array
     */
    protected $data = [];

    /**
     * Where conditions
     *
     * @var array
     */
    protected $where = [];

    /**
     * Group by clause
     *
     * @var string
     */
    protected $groupBy;

    /**
     * Having clause
     *
     * @var array
     */
    protected $having = [];

    /**
     * Order by clause
     *
     * @var string
     */
    protected $orderBy;

    /**
     * Limit
     *
     * @var int
     */
    protected $limit;

    /**
     * Offset
     *
     * @var int
     */
    protected $offset;

    /**
     * Constructor
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get SQL select query as string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getQuery();
    }

    /**
     * Select query
     *
     * @param array|string $expression
     * @return self
     */
    public function select($expression = '*')
    {
        if (!is_array($expression)) {
            $expression = explode(',', $expression);
        }

        $this->type        = self::TYPE_SELECT;
        $this->expressions = array_merge($this->expressions, array_map(function ($expression, $alias) {
            return is_string($alias) ? "$expression AS $alias" : $expression;
        }, $expression, array_keys($expression)));

        return $this;
    }

    /**
     * Insert query
     *
     * @param string $table (optional)
     * @return self
     */
    public function insert($table = null)
    {
        $this->type = self::TYPE_INSERT;
        if ($table) {
            $this->table($table);
        }

        return $this;
    }

    /**
     * Update query
     *
     * @param string       $table (optional)
     * @return self
     */
    public function update($table = null)
    {
        $this->type = self::TYPE_UPDATE;
        if ($table) {
            $this->table($table);
        }

        return $this;
    }

    /**
     * Delete query
     *
     * @param string       $table (optional)
     * @return self
     */
    public function delete($table = null)
    {
        $this->type = self::TYPE_DELETE;
        if ($table) {
            $this->table($table);
        }

        return $this;
    }

    /**
     * Table to insert, update or delete from/into
     *
     * @param string $table
     * @param string $alias (optional)
     * @return self
     */
    public function table($table, $alias = null)
    {
        if ($alias) {
            $table = [$alias => $table];
        } elseif (!is_array($table)) {
            $table = [$table];
        }

        $this->joins = [];
        foreach ($table as $alias => $name) {
            if (is_string($alias)) {
                $this->tables[$alias] = "$name $alias";
            } else {
                $this->tables[$name] = $name;
            }
        }

        return $this;
    }

    /**
     * From table
     *
     * @param string $table
     * @param string $alias (optional)
     * @return self
     */
    public function from($table, $alias = null)
    {
        return $this->table($table, $alias);
    }

    /**
     * Into table
     *
     * @param string $table
     * @param string $alias (optional)
     * @return self
     */
    public function into($table, $alias = null)
    {
        return $this->table($table, $alias);
    }

    /**
     * Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @param string $type
     * @return self
     */
    public function join($table, $alias = null, $on = null, $type = 'INNER JOIN')
    {
        $this->joins[$alias] = "$type $table $alias ON $on";

        return $this;
    }

    /**
     * LEFT OUTER Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @return self
     */
    public function leftJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, 'LEFT JOIN');
    }

    /**
     * RIGHT OUTER Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @return self
     */
    public function rightJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, 'RIGHT JOIN');
    }

    /**
     * INNER Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @return self
     */
    public function innerJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, 'INNER JOIN');
    }

    /**
     * Data to insert
     *
     * @param array $data
     * @return self
     */
    public function values(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Data to set
     *
     * @param array $data
     * @return self
     */
    public function set(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Where condition
     *
     * @param array|string $conditions
     * @param mixed ...    $parameters (optional)
     * @return self
     */
    public function where($conditions)
    {
        if (is_array($conditions)) {
            $this->where = array_merge($this->where, array_map(function ($value, $field) {
                $field = $this->connection->quoteIdentifier($field);
                if ($value === null) {
                    return $field . ' IS NULL';
                }

                return $field . '=' . $this->connection->quote($value);
            }, $conditions, array_keys($conditions)));
        } else {
            if (func_num_args() > 1) {
                $parameters = array_slice(func_get_args(), 1);
                $conditions = $this->connection->merge($conditions, $parameters);
            }
            $this->where[] = $conditions;
        }

        return $this;
    }

    /**
     * Group by column
     *
     * @param string $groupBy
     * @return self
     */
    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;

        return $this;
    }


    /**
     * Having condition
     *
     * @param array|string $conditions
     * @param mixed ...    $parameters (optional)
     * @return self
     */
    public function having($conditions)
    {
        if (is_array($conditions)) {
            $this->having = array_merge($this->where, array_map(function ($value, $field) {
                return $field . '=' . $this->connection->quote($value);
            }, $conditions, array_keys($conditions)));
        } else {
            if (func_num_args() > 1) {
                $parameters = array_slice(func_get_args(), 1);
                $conditions = $this->connection->merge($conditions, $parameters);
            }
            $this->having[] = $conditions;
        }

        return $this;
    }

    /**
     * Order by column
     *
     * @param string $orderBy
     * @return self
     */
    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Limit number of results
     *
     * @param int $limit
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Return the number of results, starting at offset
     *
     * @param int $offset
     * @return self
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get select expression
     *
     * @return string
     */
    public function getSelect()
    {
        return implode(',', $this->expressions);
    }

    /**
     * Get table
     *
     * @return string
     */
    public function getTable()
    {
        return implode(',', $this->tables);
    }

    /**
     * Get columns
     *
     * @return string
     */
    public function getColumns()
    {
        return '(' . implode(',', array_map([$this->connection, 'quoteIdentifier'], array_keys($this->data))) . ')';
    }

    /**
     * Get values
     *
     * @return string
     */
    public function getValues()
    {
        return '(' . implode(',', array_map([$this->connection, 'quote'], $this->data)) . ')';
    }

    /**
     * Get values
     *
     * @return string
     */
    public function getSet()
    {
        $format = function ($value, $field) {
            return $this->connection->quoteIdentifier($field) . '=' . $this->connection->quote($value);
        };

        return implode(',', array_map($format, $this->data, array_keys($this->data)));
    }

    /**
     * Get from query part
     *
     * @return string
     */
    public function getFrom()
    {
        return implode("\n", array_merge([$this->getTable()], $this->joins));
    }

    /**
     * Get where query part
     *
     * @return string
     */
    public function getWhere()
    {
        return implode("\nAND ", $this->where);
    }

    /**
     * Get group by query part
     *
     * @return string
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * Get having query part
     *
     * @return string
     */
    public function getHaving()
    {
        return implode("\nAND ", $this->having);
    }

    /**
     * Get order by query part
     *
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Get limit query part
     *
     * @return string
     */
    public function getLimit()
    {
        if ($this->offset && $this->limit) {
            return $this->offset . ',' . $this->limit;
        }

        return (string) $this->limit;
    }

    /**
     * Get SQL select query
     *
     * @return string
     */
    public function getSelectQuery()
    {
        $sql = 'SELECT ' . $this->getSelect();
        $sql .= "\nFROM " . $this->getFrom();
        if ($this->where) {
            $sql .= "\nWHERE " . $this->getWhere();
        }
        if ($this->groupBy) {
            $sql .= "\nGROUP BY " . $this->getGroupBy();
        }
        if ($this->having) {
            $sql .= "\nHAVING " . $this->getHaving();
        }
        if ($this->orderBy) {
            $sql .= "\nORDER BY " . $this->getOrderBy();
        }
        if ($this->limit) {
            $sql .= "\nLIMIT " . $this->getLimit();
        }

        return $sql;
    }

    /**
     * Get SQL insert query
     *
     * @return string
     */
    public function getInsertQuery()
    {
        $sql = 'INSERT INTO ' . $this->getTable();
        $sql .= "\n" . $this->getColumns();
        $sql .= "\nVALUES " . $this->getValues();

        return $sql;
    }

    /**
     * Get SQL update query
     *
     * @return string
     */
    public function getUpdateQuery()
    {
        $sql = 'UPDATE ' . $this->getTable();
        $sql .= "\nSET " . $this->getSet();
        if ($this->where) {
            $sql .= "\nWHERE " . $this->getWhere();
        }
        if ($this->orderBy) {
            $sql .= "\nORDER BY " . $this->getOrderBy();
        }
        if ($this->limit) {
            $sql .= "\nLIMIT " . $this->getLimit();
        }

        return $sql;
    }

    /**
     * Get SQL delete query
     *
     * @return string
     */
    public function getDeleteQuery()
    {
        $sql = 'DELETE FROM ' . $this->getTable();
        if ($this->where) {
            $sql .= "\nWHERE " . $this->getWhere();
        }
        if ($this->limit) {
            $sql .= "\nLIMIT " . $this->getLimit();
        }

        return $sql;
    }

    /**
     * Get SQL Query
     *
     * @return string
     */
    public function getQuery()
    {
        if (!$this->type) {
            throw new \RuntimeException('No query type set');
        }

        return $this->{'get' . $this->type . 'Query'}();
    }

    /**
     * Run this query and return the results
     *
     * @return Result
     */
    public function query()
    {
        return $this->connection->query($this->getQuery());
    }

    /**
     * Execute the query and return the number of rows affected
     *
     * @return int
     */
    public function execute()
    {
        return $this->connection->execute($this->getQuery());
    }
}
