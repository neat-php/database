<?php

namespace Neat\Database;

use Exception;
use PDOException;

class QueryException extends Exception
{
    /** @var string */
    protected $query;

    /** @var string */
    protected $state;

    /** @var string */
    protected $driverCode;

    /** @var string */
    protected $driverMessage;

    /**
     * Constructor
     *
     * @param PDOException $exception
     * @param string       $query
     */
    public function __construct(PDOException $exception, string $query)
    {
        parent::__construct($exception->getMessage(), 0, $exception);

        $this->query = $query;

        list($this->state, $this->driverCode, $this->driverMessage) = $exception->errorInfo;
    }

    /**
     * Get query
     *
     * @return string
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Get ANSI SQLSTATE
     *
     * @return string
     */
    public function state()
    {
        return $this->state;
    }

    /**
     * Get driver-specific error code
     *
     * @return string
     */
    public function driverCode()
    {
        return $this->driverCode;
    }

    /**
     * Get driver-specific error message
     *
     * @return string
     */
    public function driverMessage()
    {
        return $this->driverMessage;
    }
}
