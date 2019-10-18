<?php

namespace Neat\Database\Query;

use Neat\Database\Connection;
use Neat\Database\Query;

class NotIn extends In
{
    /**
     * Should return a quoted string with the operator and the condition part
     *
     * @param Connection $connection
     * @return string
     */
    public function getCondition(Connection $connection): string
    {
        if ($this->data instanceof Query) {
            return " NOT IN ({$this->data->getSelectQuery()})";
        }
        if (is_array($this->data)) {
            $quotedString = $connection->quote($this->data);

            return " NOT IN ($quotedString)";
        }

        return " NOT IN ($this->data)";
    }
}
