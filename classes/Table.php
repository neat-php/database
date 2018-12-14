<?php

namespace Neat\Database;

class Table
{
    /**
     * The connection to which the table should be read/written to and from
     *
     * @var Connection
     */
    private $connection;

    /**
     * The table name
     * For example:
     *      'users'
     *
     * @var string
     */
    private $name;

    /**
     * The column names of the (composed)key
     * For example:
     *      ['id']
     *      ['user_id', 'group_id']
     *
     * @var array
     */
    private $keys;

    /**
     * Table constructor.
     * @param Connection $connection
     * @param string $name
     * @param array $keys
     */
    public function __construct(Connection $connection, string $name, array $keys)
    {
        $this->connection = $connection;
        $this->name       = $name;
        $this->keys       = $keys;
    }

    /**
     * @return Query
     */
    public function query()
    {
        $query = $this->connection
            ->select('*')->from($this->name);

        return $query;
    }

    /**
     * Builds the query for searching the table by identifier and executes it
     * Will return an empty result if the record can't be found or an result with exactly 1 row
     *
     * @param int|string|array $id
     * @return Result
     */
    public function findById($id): Result
    {
        return $this->findOne($this->where($id));
    }

    /**
     * Builds the query for the given conditions and executes it
     * Will return an result with 1 or 0 rows
     *
     * @param array|string $conditions
     * @param string|null $orderBy
     * @return Result
     */
    public function findOne($conditions, string $orderBy = null): Result
    {
        $query = $this->query()
            ->where($conditions)
            ->limit(1);

        if ($orderBy) {
            $query->orderBy($orderBy);
        }

        return $query->query();
    }

    /**
     * @param string|array|null $conditions
     * @param string|null $orderBy
     * @return Result
     */
    public function findAll($conditions = null, string $orderBy = null): Result
    {
        $query = $this->query();
        if ($conditions) {
            $query->where($conditions);
        }

        if ($orderBy) {
            $query->orderBy($orderBy);
        }

        return $query->query();
    }

    /**
     * @param int|string|array $id
     * @return boolean
     */
    public function exists($id): bool
    {
        return $this->connection
                ->select('count(1)')->from($this->name)->where($this->where($id))->limit(1)
                ->query()->value() === '1';
    }

    /**
     * @param array $data
     * @return int
     */
    public function create(array $data)
    {
        $this->connection
            ->insert($this->name, $data);

        return $this->connection->insertedId();
    }

    /**
     * @param int|string|array $id
     * @param array $data
     * @return false|int
     */
    public function update($id, array $data)
    {
        return $this->connection
            ->update($this->name, $data, $this->where($id));
    }

    /**
     * Validates the identifier to prevent unexpected behaviour
     *
     * @param int|string|array $id
     */
    public function validateIdentifier($id)
    {
        $printed = print_r($id, true);
        if (count($this->keys) > 1 && !is_array($id)) {
            throw new \RuntimeException("Entity $this->name has a composed key, finding by id requires an array, given: $printed");
        }
        if (count($this->keys) !== count($id)) {
            $keys = print_r($this->keys, true);
            throw new \RuntimeException("Entity $this->name requires the following keys: $keys, given: $printed");
        }
    }

    /**
     * Creates the where condition for the identifier
     *
     * @param int|string|array $id
     * @return array
     */
    public function where($id)
    {
        $this->validateIdentifier($id);

        if (!is_array($id)) {
            $key = reset($this->keys);
            return [$key => $id];
        } else {
            return $id;
        }
    }
}
