<?php namespace Phrodo\Database;

use PDO;

/**
 * Connection class
 */
class Connection implements Contract\Connection
{

    /**
     * PDO connection
     *
     * @var PDO
     */
    protected $pdo;

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
     * Run a query and return the most appropriate result
     *
     * @param string $query
     * @param mixed  ... $data
     * @return Result|int
     */
    public function __invoke($query)
    {
        if (func_num_args() > 1) {
            $query = $this->merge($query, array_slice(func_get_args(), 1));
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
     * @param string|null $value
     * @return string
     */
    public function quote($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($value instanceof \DateTimeInterface) {
            return $this->pdo->quote($value->format('Y-m-d H:i:s'));
        }

        return $this->pdo->quote($value);
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
     * @param string $query
     * @param mixed  ... $data
     * @return Result
     */
    public function query($query)
    {
        if (func_num_args() > 1) {
            $query = $this->merge($query, array_slice(func_get_args(), 1));
        }

        $statement = $this->pdo->query($query);

        return new Result($statement);
    }

    /**
     * Execute a query and return the number of rows affected
     *
     * @param string $query
     * @param mixed  ... $data
     * @return int
     */
    public function execute($query)
    {
        if (func_num_args() > 1) {
            $query = $this->merge($query, array_slice(func_get_args(), 1));
        }

        return $this->pdo->exec($query);
    }

    /**
     * Build a query
     *
     * @return Query
     */
    public function build()
    {
        return new Query($this);
    }

    /**
     * Select data from the database
     *
     * @param string|array $expression (optional, defaults to *)
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
     * executed and the number of rows affected will be returned.
     *
     * @param string $table
     * @param array  $data  (optional)
     * @return Query|int
     */
    public function insert($table, array $data = null)
    {
        $insert = $this->build()->insert($table, $data);
        if ($table && $data) {
            return $insert->execute();
        }

        return $insert;
    }

    /**
     * Update data in a table
     *
     * When all parameters are specified, the update query is immediately
     * executed and the number of rows affected will be returned.
     *
     * @param string       $table
     * @param array        $data  (optional)
     * @param array|string $where (optional)
     * @return Query|int
     */
    public function update($table, array $data = null, $where = null)
    {
        $update = $this->build()->update($table, $data, $where);
        if ($table && $data && $where) {
            return $update->execute();
        }

        return $update;
    }

    /**
     * Delete from a table
     *
     * When all parameters are specified, the delete query is immediately
     * executed and the number of rows affected will be returned.
     *
     * @param string       $table
     * @param array|string $where (optional)
     * @return Query|int
     */
    public function delete($table, $where = null)
    {
        $delete = $this->build()->delete($table, $where);
        if ($table && $where) {
            return $delete->execute();
        }

        return $delete;
    }

    /**
     * Run a closure wrapping it in a transaction
     *
     * @param callable $closure
     * @return Transaction|mixed
     */
    public function transaction(callable $closure = null)
    {
        $transaction = new Transaction($this->pdo);
        if ($closure) {
            $transaction->run($closure);
        }

        return $transaction;
    }

}
