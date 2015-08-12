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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function table($table, $alias = null)
    {
        $this->tables[$alias ?: $table] = $alias ? "$table $alias" : $table;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function from($table, $alias = null)
    {
        return $this->table($table, $alias);
    }

    /**
     * @inheritdoc
     */
    public function into($table, $alias = null)
    {
        return $this->table($table, $alias);
    }

    /**
     * @inheritdoc
     */
    public function join($table, $alias = null, $on = null, $type = "INNER JOIN")
    {
        $this->joins[$alias] = "$type $table $alias ON $on";

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function leftJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, "LEFT JOIN");
    }

    /**
     * @inheritdoc
     */
    public function rightJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, "RIGHT JOIN");
    }

    /**
     * @inheritdoc
     */
    public function innerJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, "INNER JOIN");
    }

    /**
     * @inheritdoc
     */
    public function set(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function having($condition)
    {
        $this->having[] = $condition;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function limit($limit, $offset = 0)
    {
        $this->limit = ($offset ? "$offset," : "") . $limit;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orFail($message = "No rows found or affected")
    {
        $this->alternative = function () use ($message) {
            throw new \RuntimeException($message);
        };

        return $this;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function is($type)
    {
        return strtoupper($type) == $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getSelect()
    {
        return $this->expressions;
    }

    /**
     * @inheritdoc
     */
    public function getTable()
    {
        return implode(',', $this->tables);
    }

    /**
     * @inheritdoc
     */
    public function getFrom()
    {
        return implode("\n", array_merge([$this->getTable()], $this->joins));
    }

    /**
     * @inheritdoc
     */
    public function getWhere()
    {
        return implode("\nAND ", $this->where);
    }

    /**
     * @inheritdoc
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @inheritdoc
     */
    public function getHaving()
    {
        return implode("\nAND ", $this->having);
    }

    /**
     * @inheritdoc
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @inheritdoc
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getInsertQuery()
    {
        $sql = 'INSERT INTO ' . $this->getTable();
        $sql .= "\n(" . implode(',', array_keys($this->data)) . ')';
        $sql .= "\nVALUES (" . implode(',', array_map([$this->connection, 'quote'], $this->data)) . ')';

        return $sql;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getQuery()
    {
        if (!$this->type) {
            throw new \RuntimeException('No query type set');
        }

        return $this->{'get' . $this->type . 'Query'}();
    }

    /**
     * @inheritdoc
     */
    public function query()
    {
        return $this->connection->query($this->getQuery());
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function each(callable $closure)
    {
        return $this->query()->each($closure);
    }

    /**
     * @inheritdoc
     */
    public function rows()
    {
        return $this->query()->rows();
    }

    /**
     * @inheritdoc
     */
    public function row()
    {
        return $this->query()->row();
    }

    /**
     * @inheritdoc
     */
    public function values($column = 0)
    {
        return $this->query()->values($column);
    }

    /**
     * @inheritdoc
     */
    public function value($column = 0)
    {
        return $this->query()->value($column);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return $this->query()->getIterator();
    }

}
