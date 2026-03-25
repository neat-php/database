<?php

namespace Neat\Database;

use DateTimeInterface;
use RuntimeException;

class Query implements QueryInterface
{
    use QueryTrait;

    const TYPE_SELECT = 'SELECT';
    const TYPE_INSERT = 'INSERT';
    const TYPE_UPDATE = 'UPDATE';
    const TYPE_UPSERT = 'UPSERT';
    const TYPE_DELETE = 'DELETE';

    /** @var Connection */
    protected $connection;

    /** @var string */
    protected $type;

    /** @var array */
    protected $expressions = [];

    /** @var array */
    protected $tables = [];

    /** @var array */
    protected $joins = [];

    /** @var array */
    protected $values = [];

    /** @var array */
    protected $set = [];

    /** @var array */
    protected $where = [];

    /** @var string|null */
    protected $groupBy = null;

    /** @var array */
    protected $having = [];

    /** @var string|null */
    protected $orderBy = null;

    /** @var int|null */
    protected $limit = null;

    /** @var int|null */
    protected $offset = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array|string $expression
     * @return $this
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
     * @param string|null $table
     * @return $this
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
     * @param string|null $table
     * @return $this
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
     * @param string|null $table
     * @return $this
     */
    public function upsert($table = null)
    {
        $this->type = self::TYPE_UPSERT;
        if ($table) {
            $this->table($table);
        }

        return $this;
    }

    /**
     * @param string|null $table
     * @return $this
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
     * @param array|string $table
     * @param string|null $alias
     * @return $this
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
            if ($name instanceof QueryInterface) {
                $expression = '(' . $name->getQuery() . ')';
            } else {
                $expression = $this->connection->quoteIdentifier($name);
            }
            if (is_string($alias)) {
                $this->tables[$alias] = $expression . ' ' . $alias;
            } else {
                $this->tables[$name] = $expression;
            }
        }

        return $this;
    }

    /**
     * @param array|string $table
     * @param string|null $alias
     * @return $this
     */
    public function from($table, $alias = null)
    {
        return $this->table($table, $alias);
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return $this
     */
    public function into($table, $alias = null)
    {
        return $this->table($table, $alias);
    }

    /**
     * @param Query|string $table
     * @param string|null $alias
     * @param string|null $on
     * @param string $type
     * @return $this
     */
    public function join($table, $alias = null, $on = null, $type = 'INNER JOIN')
    {
        if ($table instanceof Query) {  // Subquery?
            $table = '(' . $table->getQuery() . ')';
        } else {
            $table = $this->connection->quoteIdentifier($table);
        }

        $this->joins[$alias] = "$type $table $alias ON $on";

        return $this;
    }

    /**
     * @param Query|string $table
     * @param string $alias
     * @param string $on
     * @return $this
     */
    public function leftJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, 'LEFT JOIN');
    }

    /**
     * @param Query|string $table
     * @param string|null $alias
     * @param string|null $on
     * @return $this
     */
    public function rightJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, 'RIGHT JOIN');
    }

    /**
     * @param Query|string $table
     * @param string|null $alias
     * @param string|null $on
     * @return $this
     */
    public function innerJoin($table, $alias = null, $on = null)
    {
        return $this->join($table, $alias, $on, 'INNER JOIN');
    }

    /**
     * @param array $data
     * @return $this
     */
    public function values(array $data)
    {
        $this->values = $data;

        return $this;
    }

    /**
     * @return $this
     */
    public function set(array $data)
    {
        $this->set = $data;

        return $this;
    }

    /**
     * @param array|string $conditions
     * @param array|bool|DateTimeInterface|int|null|string ...$parameters
     * @return $this
     */
    public function where($conditions, ...$parameters)
    {
        if (is_array($conditions)) {
            $this->where = array_merge($this->where, array_map(function ($value, $field) {
                $field = $this->connection->quoteIdentifier($field);
                if ($value === null) {
                    return $field . ' IS NULL';
                }
                if (is_array($value)) {
                    return $field . ' IN (' . $this->connection->quote($value) . ')';
                }

                return $field . '=' . $this->connection->quote($value);
            }, $conditions, array_keys($conditions)));
        } else {
            if ($parameters) {
                $conditions = $this->connection->merge($conditions, $parameters);
            }
            $this->where[] = $conditions;
        }

        return $this;
    }

    /**
     * @param string $groupBy
     * @return $this
     */
    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    /**
     * @param array|string $conditions
     * @param mixed ...$parameters
     * @return $this
     */
    public function having($conditions, ...$parameters)
    {
        if (is_array($conditions)) {
            $this->having = array_merge($this->where, array_map(function ($value, $field) {
                return $field . '=' . $this->connection->quote($value);
            }, $conditions, array_keys($conditions)));
        } else {
            if ($parameters) {
                $conditions = $this->connection->merge($conditions, $parameters);
            }
            $this->having[] = $conditions;
        }

        return $this;
    }

    /**
     * @param string $orderBy
     * @return $this
     */
    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return string
     */
    public function getSelect()
    {
        return implode(',', $this->expressions);
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return implode(',', $this->tables);
    }

    /**
     * @return string
     */
    public function getColumns()
    {
        return '(' . implode(',', array_map([$this->connection, 'quoteIdentifier'], array_keys($this->values))) . ')';
    }

    /**
     * @return string
     */
    public function getValues()
    {
        return '(' . implode(',', array_map([$this->connection, 'quote'], $this->values)) . ')';
    }

    /**
     * @return string
     */
    public function getSet()
    {
        $format = function ($value, $field) {
            return $this->connection->quoteIdentifier($field) . '=' . $this->connection->quote($value);
        };

        return implode(',', array_map($format, $this->set, array_keys($this->set)));
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return implode("\n", array_merge([$this->getTable()], $this->joins));
    }

    /**
     * @return string
     */
    public function getWhere()
    {
        return implode("\nAND ", $this->where);
    }

    /**
     * @return string
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @return string
     */
    public function getHaving()
    {
        return implode("\nAND ", $this->having);
    }

    /**
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
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
     * @return string
     */
    public function getUpsertQuery()
    {
        $sql = 'INSERT INTO ' . $this->getTable();
        $sql .= "\n" . $this->getColumns();
        $sql .= "\nVALUES " . $this->getValues();
        $sql .= "\nON DUPLICATE KEY UPDATE";
        $sql .= "\n" . $this->getSet();

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

    public function getQuery(): string
    {
        switch ($this->type) {
            case self::TYPE_SELECT:
                return $this->getSelectQuery();
            case self::TYPE_INSERT:
                return $this->getInsertQuery();
            case self::TYPE_UPDATE:
                return $this->getUpdateQuery();
            case self::TYPE_UPSERT:
                return $this->getUpsertQuery();
            case self::TYPE_DELETE:
                return $this->getDeleteQuery();
            default:
                throw new RuntimeException('No query type set');
        }
    }

    protected function connection(): Connection
    {
        return $this->connection;
    }
}
