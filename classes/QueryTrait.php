<?php

namespace Neat\Database;

/**
 * Adds methods to implement the QueryInterface
 */
trait QueryTrait
{
    abstract public function getQuery(): string;

    abstract protected function connection(): Connection;

    /**
     * Get SQL select query as string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getQuery();
    }

    /**
     * Run this query and return the results
     *
     * @return Result
     * @throws QueryException
     */
    public function query()
    {
        return $this->connection()->query($this->getQuery());
    }

    /**
     * Run this query and return the fetched results
     *
     * @return FetchedResult
     * @throws QueryException
     */
    public function fetch()
    {
        return $this->connection()->fetch($this->getQuery());
    }

    /**
     * Execute the query and return the number of rows affected
     *
     * @return int
     * @throws QueryException
     */
    public function execute()
    {
        return $this->connection()->execute($this->getQuery());
    }
}
