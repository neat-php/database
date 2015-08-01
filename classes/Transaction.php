<?php namespace Phrodo\Database;

/**
 * Transaction class
 */
class Transaction implements Contract\Transaction
{

    /**
     * Connection
     *
     * @var Contract\Connection
     */
    protected $connection;

    /**
     * Locks
     *
     * @var array
     */
    protected $locks = [];

    /**
     * Lock status
     *
     * @var bool
     */
    protected $locked = false;

    /**
     * Transaction status
     *
     * @var bool
     */
    protected $started = false;

    /**
     * Constructor
     *
     * @param Contract\Connection $connection
     */
    public function __construct(Contract\Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set locks
     *
     * @param string|array $tables
     * @param string       $type   'READ' or 'WRITE'
     * @return $this
     */
    public function withLock($tables, $type)
    {
        $tables = is_array($tables) ? $tables : [$tables];
        $type   = strtoupper($type);
        $format = function ($table, $alias) use ($type) {
            return $table . (is_string($alias) ? " AS $alias " : ' ')  . $type;
        };

        $this->locks = array_merge($this->locks, array_map($format, $tables));

        return $this;
    }

    /**
     * Set read locks
     *
     * @param string|array $tables
     * @return $this
     */
    public function withReadLock($tables)
    {
        return $this->withLock($tables, 'READ');
    }

    /**
     * Set write locks
     *
     * @param string|array $tables
     * @return $this
     */
    public function withWriteLock($tables)
    {
        return $this->withLock($tables, 'WRITE');
    }

    /**
     * Get locks
     *
     * @return array
     */
    public function getLocks()
    {
        return implode(',', $this->locks);
    }

    /**
     * Acquire table locks
     */
    public function lock()
    {
        if (!$this->locks) {
            return;
        }
        if ($this->locked) {
            throw new \RuntimeException('Locking tables when already locked causes existing locks to be released');
        }

        $this->connection->execute('LOCK TABLES ' . $this->getLocks());
        $this->locked = true;
    }

    /**
     * Release table locks
     */
    public function unlock()
    {
        if (!$this->locks) {
            return;
        }
        if (!$this->locked) {
            throw new \RuntimeException('Cannot release locks when none are acquired');
        }

        $this->connection->execute('UNLOCK TABLES');
        $this->locked = false;
    }

    /**
     * Start transaction
     */
    public function start()
    {
        if ($this->started) {
            throw new \RuntimeException('Cannot start nested transaction');
        }
        if (!$this->connection->pdo()->beginTransaction()) {
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
            throw new \RuntimeException('Cannot commit transaction when none was started');
        }
        if (!$this->connection->pdo()->commit()) {
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
            throw new \RuntimeException('Cannot rollback transaction when none was started');
        }
        if (!$this->connection->pdo()->rollBack()) {
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
     */
    public function run(callable $closure)
    {
        try {
            $this->start();
            $this->lock();
            $result = $closure();
            $this->commit();
            $this->unlock();

            return $result;
        } catch (\Throwable $e) {
            if ($this->started) {
                $this->rollback();
            }
            if ($this->locked) {
                $this->unlock();
            }

            throw $e;
        } catch (\Exception $e) {
            if ($this->started) {
                $this->rollback();
            }
            if ($this->locked) {
                $this->unlock();
            }

            throw $e;
        }
    }

}
