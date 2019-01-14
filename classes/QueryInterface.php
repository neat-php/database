<?php

namespace Neat\Database;

interface QueryInterface
{
    /**
     * Get query string
     *
     * @return string
     */
    public function getQuery(): string;

    /**
     * Run this query and return the results
     *
     * @return Result
     * @throws QueryException
     */
    public function query();

    /**
     * Run this query and return the fetched results
     *
     * @return FetchedResult
     * @throws QueryException
     */
    public function fetch();

    /**
     * Execute the query and return the number of rows affected
     *
     * @return int
     * @throws QueryException
     */
    public function execute();
}
