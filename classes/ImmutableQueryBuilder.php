<?php

namespace Neat\Database;

class ImmutableQueryBuilder extends BaseQueryBuilder
{
    use QueryTrait;

    public function select($expression = '*'): QueryBuilder
    {
        return $this->mutation()->setSelect($expression);
    }

    public function insert(string $table = null): QueryBuilder
    {
        return $this->mutation()->setInsert($table);
    }

    public function update(string $table = null): QueryBuilder
    {
        return $this->mutation()->setUpdate($table);
    }

    public function upsert(string $table = null): QueryBuilder
    {
        return $this->mutation()->setUpsert($table);
    }

    public function delete(string $table = null): QueryBuilder
    {
        return $this->mutation()->setDelete($table);
    }

    public function from($table, string $alias = null): QueryBuilder
    {
        return $this->mutation()->setFrom($table, $alias);
    }

    public function into(string $table, string $alias = null): QueryBuilder
    {
        return $this->mutation()->setInto($table);
    }

    public function join($table, string $alias = null, string $on = null, string $type = 'INNER JOIN'): QueryBuilder
    {
        return $this->mutation()->setJoin($table, $alias, $on, $type);
    }

    public function innerJoin($table, string $alias = null, string $on = null): QueryBuilder
    {
        return $this->mutation()->setInnerJoin($table, $alias, $on);
    }

    public function leftJoin($table, string $alias = null, string $on = null): QueryBuilder
    {
        return $this->mutation()->setLeftJoin($table, $alias, $on);
    }

    public function rightJoin($table, string $alias = null, string $on = null): QueryBuilder
    {
        return $this->mutation()->setRightJoin($table, $alias, $on);
    }

    public function values(array $data): QueryBuilder
    {
        return $this->mutation()->setvalues($data);
    }

    public function set(array $data): QueryBuilder
    {
        return $this->mutation()->setset($data);
    }

    public function where($conditions, ...$parameters): QueryBuilder
    {
        return $this->mutation()->setWhere($conditions, ...$parameters);
    }

    public function groupBy(string $groupBy): QueryBuilder
    {
        return $this->mutation()->setGroupBy($groupBy);
    }

    public function having($conditions, ...$parameters): QueryBuilder
    {
        return $this->mutation()->setHaving($conditions, ...$parameters);
    }

    public function orderBy(string $orderBy): QueryBuilder
    {
        return $this->mutation()->setOrderBy($orderBy);
    }

    public function limit(int $limit = null): QueryBuilder
    {
        return $this->mutation()->setLimit($limit);
    }

    public function offset(int $offset = null): QueryBuilder
    {
        return $this->mutation()->setOffset($offset);
    }

    private function mutation(): self
    {
        return clone $this;
    }
}
