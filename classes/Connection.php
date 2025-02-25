<?php

namespace Neat\Database;

use DateTimeInterface;
use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

ini_set('pcre.jit', false);

/**
 * Connection class
 */
class Connection
{
    /** @var PDO */
    protected $pdo;

    /** @var LoggerInterface */
    protected $log;

    /** @var bool */
    protected $started = false;

    /**
     * Constructor
     *
     * @param PDO $pdo
     * @param LoggerInterface|null $log
     */
    public function __construct(PDO $pdo, ?LoggerInterface $log = null)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->log = $log ?? new NullLogger();
    }

    /**
     * Run a query and return the result or number of rows affected
     *
     * @param string $query
     * @param mixed ...$data
     * @return Result|int
     * @throws QueryException
     * @deprecated Use query() or execute() instead.
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
     * @param PDO|null $pdo
     * @return PDO
     */
    public function pdo(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        }

        return $this->pdo;
    }

    /**
     * Quote a value (protecting against SQL injection)
     *
     * @param array|bool|DateTimeInterface|int|null|string $value
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
     * @param array $data
     * @return string
     */
    public function merge($query, array $data)
    {
        $expression = "/(\\?)(?=(?:[^']|'[^']*')*$)/";
        $callback = function () use (&$data) {
            if (!$data) {
                return '?';
            }

            return $this->quote(array_shift($data));
        };

        return preg_replace_callback($expression, $callback, $query);
    }

    /**
     * Execute or run a query internally
     *
     * @param string $method
     * @param string $query
     * @param array $data
     * @return PDOStatement|int
     * @throws QueryException
     */
    protected function do(string $method, string $query, array $data)
    {
        if ($data) {
            $query = $this->merge($query, $data);
        }

        try {
            $start = microtime(true);
            $result = $this->pdo->$method($query);
            $duration = microtime(true) - $start;

            $this->log->debug($query, ['duration' => $duration]);

            return $result;
        } catch (PDOException $exception) {
            $this->log->error($exception->getMessage(), [
                'exception' => $exception,
                'query' => $query,
            ]);

            throw new QueryException($exception, $query);
        }
    }

    /**
     * Run a query and return the result
     *
     * The result can be interactively fetched, but only once due to the
     * forward-only cursor being used to fetch the results.
     *
     * @param string $query
     * @param mixed ...$data
     * @return Result
     * @throws QueryException
     */
    public function query($query, ...$data)
    {
        return new Result($this->do('query', $query, $data));
    }

    /**
     * Run a query and eagerly fetch the result into memory
     *
     * Allows the result to be used more than once, for example when you
     * want to count the result and then iterate over it. Normally the
     * result would be entirely consumed after counting its rows.
     *
     * @param string $query
     * @param mixed ...$data
     * @return FetchedResult
     * @throws QueryException
     */
    public function fetch($query, ...$data)
    {
        return new FetchedResult($this->do('query', $query, $data)->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Execute a query and return the number of rows affected
     *
     * @param string $query
     * @param mixed ...$data
     * @return int
     * @throws QueryException
     */
    public function execute($query, ...$data)
    {
        return $this->do('exec', $query, $data);
    }

    /**
     * Get last inserted id
     *
     * @return int
     */
    public function insertedId()
    {
        return (int)$this->pdo->lastInsertId();
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
     * @param array|null $data
     * @return Query|int
     * @throws QueryException
     */
    public function insert($table, ?array $data = null)
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
     * @param string $table
     * @param array|null $data
     * @param array|null|string $where
     * @return Query|int
     * @throws QueryException
     */
    public function update($table, ?array $data = null, $where = null)
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
     * Atomically insert or update data in a table
     *
     * When all parameters are specified, the upsert query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param string $table
     * @param array|null $data
     * @param array|null|string $key
     * @return Query|int
     * @throws QueryException
     */
    public function upsert($table, ?array $data = null, $key = null)
    {
        $upsert = $this->build()->upsert($table);
        if ($data) {
            $upsert->values($data);
        }
        if ($key) {
            $key = array_flip(is_array($key) ? $key : (array)$key);
            $set = array_diff_key($data, $key);
            $where = array_intersect_key($data, $key);

            $upsert->set($set)->where($where);
        }
        if ($data && $key) {
            return $upsert->execute();
        }

        return $upsert;
    }

    /**
     * Delete from a table
     *
     * When all parameters are specified, the delete query is immediately
     * executed and the number of rows affected will be returned. Otherwise
     * the query builder is returned so you can extend the query further.
     *
     * @param string $table
     * @param array|null|string $where
     * @return Query|int
     * @throws QueryException
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
            throw new RuntimeException('Cannot start nested transaction');
        }
        if (!$this->pdo->beginTransaction()) {
            throw new RuntimeException('Failed to start transaction');
        }

        $this->started = true;
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        if (!$this->started) {
            throw new RuntimeException('Cannot commit transaction before start');
        }
        if (!$this->pdo->commit()) {
            throw new RuntimeException('Failed to commit transaction');
        }

        $this->started = false;
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        if (!$this->started) {
            throw new RuntimeException('Cannot rollback transaction before start');
        }
        if (!$this->pdo->rollBack()) {
            throw new RuntimeException('Failed to rollback transaction');
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
     * @throws Throwable Exceptions thrown by the closure
     */
    public function transaction(callable $closure)
    {
        $this->start();
        try {
            $result = $closure();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
        $this->commit();

        return $result;
    }
}
