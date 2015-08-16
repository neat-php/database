<?php namespace Phrodo\Database;

use Some\Database\Transaction as TransactionContract;
use PDO;

/**
 * Transaction class
 */
class Transaction implements TransactionContract
{

    /**
     * PDO
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
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
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
     * Run a closure inside a transaction and acquire locks
     *
     * Begins a transaction before running the closure and commits the
     * transaction afterwards. When the closure emits an exception or
     * throwable (PHP 7 fatal), the transaction will be rolled back.
     *
     * @param callable $closure Closure without required parameters
     * @return mixed Closure return value
     * @throws \Throwable|\Exception When the transaction fails
     * @codeCoverageIgnore Because one catch block is unreachable in PHP 5 or 7
     */
    public function run(callable $closure)
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
