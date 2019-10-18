<?php

namespace Neat\Database\Query;

use Neat\Database\Connection;

interface ConditionInterface
{
    /**
     * Should return a quoted string with the operator and the condition part
     *
     * @param Connection $connection
     * @return string
     */
    public function getCondition(Connection $connection): string;
}
