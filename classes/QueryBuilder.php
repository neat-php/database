<?php

namespace Neat\Database;

use DateTimeInterface;

interface QueryBuilder extends QueryInterface
{
    /** Select type */
    const TYPE_SELECT = 'SELECT';

    /** Insert type */
    const TYPE_INSERT = 'INSERT';

    /** Update type */
    const TYPE_UPDATE = 'UPDATE';

    /** Atomic insert/update type */
    const TYPE_UPSERT = 'UPSERT';

    /** Delete type */
    const TYPE_DELETE = 'DELETE';

    /**
     * @param array|string $expression
     * @return self
     */
    public function select($expression = '*'): self;

    /**
     * @param string|null $table
     * @return self
     */
    public function insert(string $table = null): self;

    /**
     * @param string|null $table
     * @return self
     */
    public function update(string $table = null): self;

    /**
     * @param string|null $table
     * @return self
     */
    public function upsert(string $table = null): self;

    /**
     * @param string|null $table
     * @return self
     */
    public function delete(string $table = null): self;

    /**
     * @param array|string $table
     * @param string|null  $alias
     * @return self
     */
    public function from($table, string $alias = null): self;

    /**
     * @param string|null $table
     * @param string|null $alias
     * @return self
     */
    public function into(string $table, string $alias = null): self;

    /**
     * @param QueryInterface|string $table
     * @param string|null           $alias
     * @param string|null           $on
     * @param string                $type
     * @return self
     */
    public function join($table, string $alias = null, string $on = null, string $type = 'INNER JOIN'): self;

    /**
     * @param QueryInterface|string $table
     * @param string|null           $alias
     * @param string|null           $on
     * @return self
     */
    public function innerJoin($table, string $alias = null, string $on = null): self;

    /**
     * @param QueryInterface|string $table
     * @param string|null           $alias
     * @param string|null           $on
     * @return self
     */
    public function leftJoin($table, string $alias = null, string $on = null): self;

    /**
     * @param QueryInterface|string $table
     * @param string|null           $alias
     * @param string|null           $on
     * @return self
     */
    public function rightJoin($table, string $alias = null, string $on = null): self;

    /**
     * @param array $data
     * @return self
     */
    public function values(array $data): self;

    /**
     * @param array $data
     * @return self
     */
    public function set(array $data): self;

    /**
     * Where condition.
     * If $conditions is an array no parameters should be passed.
     * The values of $conditions will be escaped in the same way as the parameters.
     *
     * $conditions transformation:
     * array should be converted to IN ('a', 'b', 'c')
     * null should be converted to IS NULL
     *
     * Escaping:
     *
     * Array's should be converted to comma separated values.
     * The values should be escaped and transformed according to their type.
     * ['a', 'b', 'c']: 'a', 'b', 'c'
     *
     * Booleans should be converted to '0' or '1'
     * false: '0'
     *
     * DateTimes should be converted to MySQL datetime format
     * new DateTime(): '2021-01-29 15:44:00'
     *
     * Integers should be escaped
     * 5: '5'
     *
     * NULL should be used as literal
     * NULL: NULL
     *
     * Strings should be escaped
     * 'test': 'test'
     *
     * @param array<string,array<bool|DateTimeInterface|int|string>|bool|DateTimeInterface|int|null|string>|string $conditions
     * @param array<bool|DateTimeInterface|int|string>|bool|DateTimeInterface|int|null|string                      ...$parameters
     * @return self
     */
    public function where($conditions, ...$parameters): self;

    /**
     * @param string $groupBy
     * @return self
     */
    public function groupBy(string $groupBy): self;

    /**
     * @param array|string $conditions
     * @param mixed        ...$parameters
     * @return self
     */
    public function having($conditions, ...$parameters): self;

    /**
     * @param string $orderBy
     * @return self
     */
    public function orderBy(string $orderBy): self;

    /**
     * @param int|null $limit
     * @return self
     */
    public function limit(int $limit = null): self;

    /**
     * @param int|null $offset
     * @return self
     */
    public function offset(int $offset = null): self;
}
