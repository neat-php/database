<?php namespace Phrodo\Database\Contract;

/**
 * Transaction interface
 */
interface Transaction
{
    /**
     * Set locks
     *
     * @param string|array $tables
     * @param string       $type   'READ' or 'WRITE'
     * @return $this
     */
    public function withLock($tables, $type);

    /**
     * Set read locks
     *
     * @param string|array $tables
     * @return $this
     */
    public function withReadLock($tables);

    /**
     * Set write locks
     *
     * @param string|array $tables
     * @return $this
     */
    public function withWriteLock($tables);

    /**
     * Get locks
     *
     * @return array
     */
    public function getLocks();

    /**
     * Acquire table locks
     */
    public function lock();

    /**
     * Release table locks
     */
    public function unlock();

    /**
     * Start transaction
     */
    public function start();

    /**
     * Commit transaction
     */
    public function commit();

    /**
     * Rollback transaction
     */
    public function rollback();

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
    public function run(callable $closure);

}
