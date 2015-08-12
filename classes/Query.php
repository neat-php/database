<?php namespace Phrodo\Database;

use IteratorAggregate;

/**
 * Query builder class
 */
class Query implements Contract\Query, IteratorAggregate
{

    /**
     * Connection
     *
     * @var Contract\Connection
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
     * @var string
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
     * Limit clause
     *
     * @var string
     */
    protected $limit;

    /**
     * Alternative action
     *
     * @var callable
     */
    protected $alternative;

    /**
     * Constructor
     *
     * @param Contract\Connection $connection
     */
    public function __construct(Contract\Connection $connection)
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
     * @return $this
     */
    public function select($expression = '*')
    {
        if (!is_array($expression)) {
            $expression = explode(',', $expression);
        }

        $this->type        = self::TYPE_SELECT;
        $this->expressions = $expression;

        return $this;
    }

    /**
     * Insert query
     *
     * @param string $table (optional)
     * @param array  $data  (optional)
     * @return $this
     */
    public function insert($table = null, array $data = null)
    {
        $this->type = self::TYPE_INSERT;
        if ($table) {
            $this->table($table);
        }
        if ($data) {
            $this->set($data);
        }

        return $this;
    }

    /**
     * Update query
     *
     * @param string       $table (optional)
     * @param array        $data  (optional)
     * @param array|string $where (optional)
     * @return $this
     */
    public function update($table = null, array $data = null, $where = null)
    {
        $this->type = self::TYPE_UPDATE;
        if ($table) {
            $this->table($table);
        }
        if ($data) {
            $this->set($data);
        }
        if ($where) {
            $this->where($where);
        }

        return $this;
    }

    /**
     * Delete query
     *
     * @param string       $table (optional)
     * @param array|string $where (optional)
     * @return $this
     */
    public function delete($table = null, $where = null)
    {
        $this->type = self::TYPE_DELETE;
        if ($table) {
            $this->table($table);
        }
        if ($where) {
            $this->where($where);
        }

        return $this;
    }

    /**
     * Use table
     *
     * @param string $table
     * @param string $alias (optional)
     * @return $this
     */
    public function table($table, $alias = null)
    {
        $this->tables[$alias ?: $table] = $alias ? "$table $alias" : $table;

        return $this;
    }

    /**
     * From table
     *
     * @param string $table
     * @param string $alias (optional)
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function join($table, $alias = null, $on = null, $type = "INNER JOIN")
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
     * @return $this
     */
    public function leftJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, "LEFT JOIN");
    }

    /**
     * RIGHT OUTER Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @return $this
     */
    public function rightJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, "RIGHT JOIN");
    }

    /**
     * INNER Join a table
     *
     * @param string $table
     * @param string $alias
     * @param string $on
     * @return $this
     */
    public function innerJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, "INNER JOIN");
    }

    /**
     * Data to set
     *
     * @param array $data
     * @return $this
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
     * @param array        $parameters
     * @return $this
     */
    public function where($conditions, $parameters = null)
    {
        if (is_array($conditions)) {
            $this->where = array_merge($this->where, array_map(function($value, $field) {
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
     * @return $this
     */
    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    /**
     * Having condition
     *
     * @param $condition
     * @return $this;
     */
    public function having($condition)
    {
        $this->having[] = $condition;

        return $this;
    }

    /**
     * Order by column
     *
     * @param string $orderBy
     * @return $this
     */
    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Limit number of results/items
     *
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit($limit, $offset = 0)
    {
        $this->limit = ($offset ? "$offset," : "") . $limit;

        return $this;
    }

    /**
     * Fail hard and throw an exception when no result can be found
     *
     * @param string $message
     * @return $this
     */
    public function orFail($message = "No rows found or affected")
    {
        $this->alternative = function () use ($message) {
            throw new \RuntimeException($message);
        };

        return $this;
    }

    /**
     * No rows affected? Insert row instead
     *
     * @return $this
     */
    public function orInsert()
    {
        $this->alternative = function () {
            $this->insert();

            return $this->execute();
        };

        return $this;
    }

    /**
     * Is this a select, insert, update or delete query?
     *
     * @param string $type
     * @return bool
     */
    public function is($type)
    {
        return strtoupper($type) == $this->type;
    }

    /**
     * Get select columns query part
     *
     * @return string
     */
    public function getSelect()
    {
        return $this->expressions;
    }

    /**
     * Get tables
     *
     * @return string
     */
    public function getTable()
    {
        return implode(',', $this->tables);
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
        return $this->limit;
    }

    /**
     * Get select query
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
     * Get insert query
     *
     * @return string
     */
    public function getInsertQuery()
    {
        $sql = 'INSERT INTO ' . $this->getTable();
        $sql .= "\n(" . implode(',', array_keys($this->data)) . ')';
        $sql .= "\nVALUES (" . implode(',', array_map([$this->connection, 'quote'], $this->data)) . ')';

        return $sql;
    }

    /**
     * Get update query
     *
     * @return string
     */
    public function getUpdateQuery()
    {
        $sql = 'UPDATE ' . $this->getTable();
        $sql .= "\nSET " . implode(',', array_map(function($value, $field) {
            return $field . '=' . $this->connection->quote($value);
        }, $this->data, array_keys($this->data)));
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
     * Get delete query
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
     * Run a query and return the results
     *
     * @note Query will not fail with an exception when empty
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
        $rows = $this->connection->execute($this->getQuery());
        if (!$rows && $this->alternative) {
            $this->{"alternative"}();
        }

        return $rows;
    }

    /**
     * Call a closure for each row
     *
     * @param callable $closure
     * @return array
     */
    public function each(callable $closure)
    {
        return $this->query()->each($closure);
    }

    /**
     * Get all rows as array
     *
     * @return array
     */
    public function rows()
    {
        return $this->query()->rows();
    }

    /**
     * Get the first row as array
     *
     * @return array
     */
    public function row()
    {
        return $this->query()->row();
    }

    /**
     * Get all values from one column
     *
     * @param int|string $column
     * @return array
     */
    public function values($column = 0)
    {
        return $this->query()->values($column);
    }

    /**
     * Get the first value from one column
     *
     * @param int|string $column
     * @return mixed
     */
    public function value($column = 0)
    {
        return $this->query()->value($column);
    }

    /**
     * Get a (forward-only) iterator
     *
     * @return \Generator
     */
    public function getIterator()
    {
        return $this->query()->getIterator();
    }

}
