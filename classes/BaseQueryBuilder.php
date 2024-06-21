<?php

namespace Neat\Database;

use DateTimeInterface;
use RuntimeException;

abstract class BaseQueryBuilder implements QueryBuilder
{
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

    /** @var string */
    protected $groupBy;

    /** @var array */
    protected $having = [];

    /** @var string */
    protected $orderBy;

    /** @var int */
    protected $limit;

    /** @var int */
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
     * Select query
     *
     * @param array|string $expression
     * @return $this
     */
    public function setSelect($expression = '*'): QueryBuilder
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
     * @return $this
     */
    public function setInsert(string $table = null): QueryBuilder
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
     * @param string $table (optional)
     * @return $this
     */
    public function setUpdate(string $table = null): QueryBuilder
    {
        $this->type = self::TYPE_UPDATE;
        if ($table) {
            $this->table($table);
        }

        return $this;
    }

    /**
     * Atomic insert/update query
     *
     * @param string $table (optional)
     * @return $this
     */
    public function setUpsert(string $table = null): QueryBuilder
    {
        $this->type = self::TYPE_UPSERT;
        if ($table) {
            $this->table($table);
        }

        return $this;
    }

    /**
     * Delete query
     *
     * @param string $table (optional)
     * @return $this
     */
    public function setDelete(string $table = null): QueryBuilder
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
     * @param array<string, QueryInterface|string>|string $table
     * @param string                                      $alias (optional)
     * @return $this
     */
    public function table($table, $alias = null): QueryBuilder
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
     * From table
     *
     * @param array|string $table
     * @param string       $alias (optional)
     * @return $this
     */
    public function setFrom($table, string $alias = null): QueryBuilder
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
    public function setInto(string $table, string $alias = null): QueryBuilder
    {
        return $this->table($table, $alias);
    }

    /**
     * Join a table
     *
     * @param QueryInterface|string $table
     * @param string                $alias
     * @param string                $on
     * @param string                $type
     * @return $this
     */
    public function setJoin($table, string $alias = null, string $on = null, string $type = 'INNER JOIN'): QueryBuilder
    {
        if ($table instanceof QueryInterface) {
            $table = "({$table->getQuery()})";
        } else {
            $table = $this->connection->quoteIdentifier($table);
        }

        $this->joins[$alias] = "$type $table $alias ON $on";

        return $this;
    }

    /**
     * LEFT OUTER Join a table
     *
     * @param QueryInterface|string $table
     * @param string                $alias
     * @param string                $on
     * @return $this
     */
    public function setLeftJoin($table, string $alias = null, string $on = null): QueryBuilder
    {
        return $this->join($table, $alias, $on, 'LEFT JOIN');
    }

    /**
     * RIGHT OUTER Join a table
     *
     * @param QueryInterface|string $table
     * @param string                $alias
     * @param string                $on
     * @return $this
     */
    public function setRightJoin($table, string $alias = null, string $on = null): QueryBuilder
    {
        return $this->join($table, $alias, $on, 'RIGHT JOIN');
    }

    /**
     * INNER Join a table
     *
     * @param QueryInterface|string $table
     * @param string                $alias
     * @param string                $on
     * @return $this
     */
    public function setInnerJoin($table, string $alias = null, string $on = null): QueryBuilder
    {
        return $this->join($table, $alias, $on, 'INNER JOIN');
    }

    /**
     * Data to insert
     *
     * @param array $data
     * @return $this
     */
    public function setValues(array $data): QueryBuilder
    {
        $this->values = $data;

        return $this;
    }

    /**
     * Data to set
     *
     * @param array $data
     * @return $this
     */
    public function setSet(array $data): QueryBuilder
    {
        $this->set = $data;

        return $this;
    }

    /**
     * Where condition
     *
     * @param array|string                                 $conditions
     * @param array|bool|DateTimeInterface|int|null|string ...$parameters (optional)
     * @return $this
     */
    public function setWhere($conditions, ...$parameters): QueryBuilder
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
     * Group by column
     *
     * @param string $groupBy
     * @return $this
     */
    public function setGroupBy(string $groupBy): QueryBuilder
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    /**
     * Having condition
     *
     * @param array|string $conditions
     * @param mixed        ...$parameters (optional)
     * @return $this
     */
    public function setHaving($conditions, ...$parameters): QueryBuilder
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
     * Order by column
     *
     * @param string $orderBy
     * @return $this
     */
    public function setOrderBy(string $orderBy): QueryBuilder
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Limit number of results
     *
     * @param int|null $limit
     * @return $this
     */
    public function setLimit(int $limit = null): QueryBuilder
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Return the number of results, starting at offset
     *
     * @param int|null $offset
     * @return $this
     */
    public function setOffset(int $offset = null): QueryBuilder
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get select expression
     *
     * @return string
     */
    public function getSelect(): string
    {
        return implode(',', $this->expressions);
    }

    /**
     * Get table
     *
     * @return string
     */
    public function getTable(): string
    {
        return implode(',', $this->tables);
    }

    /**
     * Get columns
     *
     * @return string
     */
    public function getColumns(): string
    {
        return '(' . implode(',', array_map([$this->connection, 'quoteIdentifier'], array_keys($this->values))) . ')';
    }

    /**
     * Get values
     *
     * @return string
     */
    public function getValues(): string
    {
        return '(' . implode(',', array_map([$this->connection, 'quote'], $this->values)) . ')';
    }

    /**
     * Get values
     *
     * @return string
     */
    public function getSet(): string
    {
        $format = function ($value, $field) {
            return $this->connection->quoteIdentifier($field) . '=' . $this->connection->quote($value);
        };

        return implode(',', array_map($format, $this->set, array_keys($this->set)));
    }

    /**
     * Get from query part
     *
     * @return string
     */
    public function getFrom(): string
    {
        return implode("\n", array_merge([$this->getTable()], $this->joins));
    }

    /**
     * Get where query part
     *
     * @return string
     */
    public function getWhere(): string
    {
        return implode("\nAND ", $this->where);
    }

    /**
     * Get group by query part
     *
     * @return string
     */
    public function getGroupBy(): string
    {
        return $this->groupBy ?? '';
    }

    /**
     * Get having query part
     *
     * @return string
     */
    public function getHaving(): string
    {
        return implode("\nAND ", $this->having);
    }

    /**
     * Get order by query part
     *
     * @return string
     */
    public function getOrderBy(): string
    {
        return $this->orderBy ?? '';
    }

    /**
     * Get limit query part
     *
     * @return string
     */
    public function getLimit(): string
    {
        if ($this->offset && $this->limit) {
            return $this->offset . ',' . $this->limit;
        }

        return (string)$this->limit;
    }

    /**
     * Get SQL select query
     *
     * @return string
     */
    public function getSelectQuery(): string
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
    public function getInsertQuery(): string
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
    public function getUpdateQuery(): string
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
     * Get SQL upsert query
     *
     * @return string
     */
    public function getUpsertQuery(): string
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
    public function getDeleteQuery(): string
    {
        /** @noinspection SqlWithoutWhere */
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
    public function getQuery(): string
    {
        switch ($this->type) {
            case QueryBuilder::TYPE_SELECT:
                return $this->getSelectQuery();
            case QueryBuilder::TYPE_INSERT:
                return $this->getInsertQuery();
            case QueryBuilder::TYPE_UPDATE:
                return $this->getUpdateQuery();
            case QueryBuilder::TYPE_UPSERT:
                return $this->getUpsertQuery();
            case QueryBuilder::TYPE_DELETE:
                return $this->getDeleteQuery();
            default:
                throw new RuntimeException('No query type set');
        }
    }

    /**
     * @return Connection
     */
    protected function connection(): Connection
    {
        return $this->connection;
    }
}
