<?php

namespace Neat\Database;

/**
 * @deprecated Use the ImmutableQueryBuilder or MutableQueryBuilder instead
 */
class Query extends BaseQueryBuilder
{
    use QueryTrait;

    public function select($expression = '*'): QueryBuilder
    {
        return $this->setSelect($expression);
    }

    public function insert(string $table = null): QueryBuilder
    {
        return $this->setInsert($table);
    }

    public function update(string $table = null): QueryBuilder
    {
        return $this->setUpdate($table);
    }

    public function upsert(string $table = null): QueryBuilder
    {
        return $this->setUpsert($table);
    }

    public function delete(string $table = null): QueryBuilder
    {
        return $this->setDelete($table);
    }

    public function from($table, string $alias = null): QueryBuilder
    {
        return $this->setFrom($table, $alias);
    }

    public function into(string $table, string $alias = null): QueryBuilder
    {
        return $this->setInto($table, $alias);
    }

    public function join($table, string $alias = null, string $on = null, string $type = 'INNER JOIN'): QueryBuilder
    {
        return $this->setJoin($table, $alias, $on, $type);
    }

    public function innerJoin($table, string $alias = null, string $on = null): QueryBuilder
    {
        return $this->setInnerJoin($table, $alias, $on);
    }

    public function leftJoin($table, string $alias = null, string $on = null): QueryBuilder
    {
        return $this->setLeftJoin($table, $alias, $on);
    }

    public function rightJoin($table, string $alias = null, string $on = null): QueryBuilder
    {
        return $this->setRightJoin($table, $alias, $on);
    }

    public function values(array $data): QueryBuilder
    {
        return $this->setvalues($data);
    }

    public function set(array $data): QueryBuilder
    {
        return $this->setset($data);
    }

    public function where($conditions, ...$parameters): QueryBuilder
    {
        return $this->setWhere($conditions, ...$parameters);
    }

    public function groupBy(string $groupBy): QueryBuilder
    {
        return $this->setGroupBy($groupBy);
    }

    public function having($conditions, ...$parameters): QueryBuilder
    {
        return $this->setHaving($conditions, ...$parameters);
    }

    public function orderBy(string $orderBy): QueryBuilder
    {
        return $this->setOrderBy($orderBy);
    }

    public function limit(int $limit = null): QueryBuilder
    {
        return $this->setLimit($limit);
    }

    public function offset(int $offset = null): QueryBuilder
    {
        return $this->setOffset($offset);
    }
}
