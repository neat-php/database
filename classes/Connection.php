<?php

namespace Neat\Database;

use DateTimeInterface;
use PDO;

ini_set('pcre.jit', false);

/**
 * Connection class
 */
class Connection
{
    /**
     * PDO connection
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * Transaction started?
     *
     * @var bool
     */
    protected $started = false;

    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Run a query and return the result or number of rows affected
     *
     * @param string $query
     * @param mixed  ...$data
     * @return Result|int
     */
    public function __invoke($query, ...$data)
    {
        if ($data) {
            $query = $this->merge($query, $data);
        }

        if (preg_match('|^\s*SELECT\s+|i', $query)) {
            return $this->query($query);
        } else {
            return $this->execute($query);
        }
    }

    /**
     * Get or set PDO instance
     *
     * @param PDO $pdo (optional)
     * @return PDO
     */
    public function pdo(PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        }

        return $this->pdo;
    }

    /**
     * Quote a value (protecting against SQL injection)
     *
     * @param string|null|DateTimeInterface|array|bool $value
     * @return string
     */
    public function quote($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($value instanceof DateTimeInterface) {
            return $this->pdo->quote($value->format('Y-m-d H:i:s'));
        }
        if (is_array($value)) {
            return implode(',', array_map([$this, 'quote'], $value));
        }
        if (is_bool($value)) {
            return $this->pdo->quote($value ? 1 : 0);
        }

        return $this->pdo->quote($value);
    }

    /**
     * Quote an identifier for MySQL query usage
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        $parts = explode(".", $identifier);
        if (count($parts) > 1) {
            return $this->quoteIdentifier($parts[0]) . '.' . $this->quoteIdentifier($parts[1]);
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Merge data into an SQL query with placeholders
     *
     * @param string $query
     * @param array  $data
     * @return string
     */
    public function merge($query, array $data)
    {
        $expression = "/(\\?)(?=(?:[^']|'[^']*')*$)/";
        $callback   = function () use (&$data) {
            if (!$data) {
                return '?';
            }

            return $this->quote(array_shift($data));
        };

        return preg_replace_callback($expression, $callback, $query);
    }

    /**
     * Run a query and return the result
     *
     * The result can be interactively fetched, but only once due to the
     * forward-only cursor being used to fetch the results.
     *
     * @param string $query
     * @param mixed  ...$data
     * @return Result
     */
    public function query($query, ...$data)
    {
        if ($data) {
            $query = $this->merge($query, $data);
        }

        $statement = $this->pdo->query($query);

        return new Result($statement);
    }

    /**
     * Run a query and eagerly fetch the result into memory
     *
     * Allows the result to be used more than once, for example when you
     * want to count the result and then iterate over it. Normally the
     * result would be entirely consumed after counting its rows.
     *
     * @param string $query
     * @param mixed  ...$data
     * @return FetchedResult
     */
    public function fetch($query, ...$data)
    {
        if ($data) {
            $query = $this->merge($query, $data);
        }

        $statement = $this->pdo->query($query);

        return new FetchedResult($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Execute a query and return the number of rows affected
     *
     * @param string $query
     * @param mixed  ...$data
     * @return int
     */
    public function execute($query, ...$data)
    {
        if ($data) {
            $query = $this->merge($query, $data);
        }

        return $this->pdo->exec($query);
    }

    /**
     * Get last inserted id
     *
     * @return int
     */
    public function insertedId()
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Build a query
     *
     * @return Query
     */
    protected function build()
    {
        return new Query($this);
    }

    /**
     * Select data from the database
     *
     * @param array|string $expression (optional, defaults to *)
     * @return Query
     */
    public function select($expression = '*')
    {
        return $this->build()->select($expression);
    }

    /**
     * Insert data into a table
     *
     * When all parameters are specified, the insert query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param string $table
     * @param array  $data (optional)
     * @return Query|int
     */
    public function insert($table, array $data = null)
    {
        $insert = $this->build()->insert($table);
        if ($data) {
            return $insert->values($data)->execute();
        }

        return $insert;
    }

    /**
     * Update data in a table
     *
     * When all parameters are specified, the update query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param string       $table
     * @param array        $data  (optional)
     * @param array|string $where (optional)
     * @return Query|int
     */
    public function update($table, array $data = null, $where = null)
    {
        $update = $this->build()->update($table);
        if ($data) {
            $update->set($data);
        }
        if ($where) {
            $update->where($where);
        }
        if ($data && $where) {
            return $update->execute();
        }

        return $update;
    }

    /**
     * Delete from a table
     *
     * When all parameters are specified, the delete query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param string       $table
     * @param array|string $where (optional)
     * @return Query|int
     */
    public function delete($table, $where = null)
    {
        $delete = $this->build()->delete($table);
        if ($where) {
            return $delete->where($where)->execute();
        }

        return $delete;
    }

    /**
     * Start transaction
     */
    public function start()
    {
        if ($this->started) {
            throw new \RuntimeException('Cannot start nested transaction');
        }
        if (!$this->pdo->beginTransaction()) {
            throw new \RuntimeException('Failed to start transaction');
        }

        $this->started = true;
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        if (!$this->started) {
            throw new \RuntimeException('Cannot commit transaction before start');
        }
        if (!$this->pdo->commit()) {
            throw new \RuntimeException('Failed to commit transaction');
        }

        $this->started = false;
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        if (!$this->started) {
            throw new \RuntimeException('Cannot rollback transaction before start');
        }
        if (!$this->pdo->rollBack()) {
            throw new \RuntimeException('Failed to rollback transaction');
        }

        $this->started = false;
    }

    /**
     * Run a closure inside a transaction
     *
     * Begins a transaction before running the closure and commits the
     * transaction afterwards. When the closure emits an exception or
     * throwable error, the transaction will be rolled back.
     *
     * @param callable $closure Closure without required parameters
     * @return mixed Closure return value
     * @throws \Throwable Exceptions thrown by the closure
     */
    public function transaction(callable $closure)
    {
        $this->start();
        try {
            $result = $closure();
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
        $this->commit();

        return $result;
    }
}
