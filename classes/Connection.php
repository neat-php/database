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

        $this->query = function ($query) {
            return $this->pdo->query($query);
        };
        $this->exec  = function ($query) {
            return $this->pdo->exec($query);
        };
    }

    /**
     * Run a query and return the most appropriate result
     *
     * @param string $query
     * @param mixed  $data
     * @return Result|int
     */
    public function __invoke($query, array $data = null)
    {
        if (!is_array($data) && func_num_args() > 1) {
            $data = array_slice(func_get_args(), 1);
        }

        if (preg_match('|^\s*SELECT\s+|i', $query)) {
            return $this->query($query, $data);
        } else {
            return $this->execute($query, $data);
        }
    }

    /**
     * Get or set PDO instance
     *
     * @param \PDO $pdo (optional)
     * @return \PDO
     */
    public function pdo(\PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        }

        return $this->pdo;
    }

    /**
     * Quote a value (protecting against SQL injection)
     *
     * @param string|array|null $value
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
        if (is_array($value)) {
            return implode(',', array_map([$this, 'quote'], $value));
        }

        return $this->pdo->quote($value);
    }

    /**
     * Merge data into an SQL query with placeholders
     *
     * @param string $query
     * @param mixed  $data
     * @return string
     */
    public function merge($query, $data = null)
    {
        if (!is_array($data) && func_num_args() > 1) {
            $data = array_slice(func_get_args(), 1);
        }

        $expression = "/(\\?)(?=(?:[^']|'[^']*')*$)/";
        $callback   = function () use (&$data) {
            return $this->quote(array_shift($data));
        };

        return preg_replace_callback($expression, $callback, $query);
    }

    /**
     * Run a query and return the result
     *
     * @param string $query
     * @param mixed  $data
     * @return Result
     */
    public function query($query, $data = null)
    {
        if (!is_array($data) && func_num_args() > 1) {
            $data = array_slice(func_get_args(), 1);
        }

        $query     = $this->merge($query, $data);
        $statement = $this->{"query"}($query);

        return new Result($statement);
    }

    /**
     * Execute a query and return the number of rows affected
     *
     * @param string $query
     * @param mixed  $data
     * @return int
     */
    public function execute($query, $data = null)
    {
        if (!is_array($data) && func_num_args() > 1) {
            $data = array_slice(func_get_args(), 1);
        }

        $query = $this->merge($query, $data);

        return $this->{"exec"}($query);
    }

    /**
     * Intercept queries
     *
     * @param callable $closure
     */
    public function intercept(callable $closure)
    {
        foreach (['query', 'exec'] as $method) {
            $original = $this->$method;

            $this->$method = function ($query) use ($closure, $original) {
                return $closure($original, $query);
            };
        }
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
     * @param string|array $select
     * @return Query
     */
    public function select($select = '*')
    {
        $select = new Query($this);
        if ($select) {
            $select->select($select);
        }

        return $select;
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
        $insert = new Query($this);
        $insert->insert($table, $data);
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
        $update = new Query($this);
        $update->update($table, $data, $where);
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
        $delete = new Query($this);
        $delete->delete($table, $where);
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
        $transaction = new Transaction($this);
        if ($closure) {
            $transaction->run($closure);
        }

        return $transaction;
    }

}
