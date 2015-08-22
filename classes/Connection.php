<?php namespace Phrodo\Database;

use Some\Database\Transaction as TransactionContract;
use Some\Database\Connection as ConnectionContract;
use Some\Database\Query as QueryContract;
use PDO;

/**
 * Connection class
 */
class Connection implements ConnectionContract, QueryContract, TransactionContract
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function fetch($query)
    {
        if (func_num_args() > 1) {
            $query = $this->merge($query, array_slice(func_get_args(), 1));
        }

        $statement = $this->pdo->query($query);

        return new FetchedResult($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @inheritdoc
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
    protected function build()
    {
        return new Query($this);
    }

    /**
     * @inheritdoc
     */
    public function select($expression = '*')
    {
        return $this->build()->select($expression);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     * @codeCoverageIgnore Because one catch block is unreachable in PHP 5 or 7
     */
    public function transaction(callable $closure)
    {
        $this->start();
        try {
            $result = $closure();
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
        $this->commit();

        return $result;
    }

}
