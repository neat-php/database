<?php

namespace Neat\Database\Query;

use Neat\Database\Connection;
use Neat\Database\Query;

class In implements ConditionInterface
{
    /**
     * @var array|Query
     */
    protected $data;

    /**
     * In constructor.
     * @param string|array|Query $data
     */
    public function __construct($data)
    {
        if (!$data instanceof Query && !is_array($data) && !is_string($data)) {
            $type = gettype($data);
            throw new \TypeError("Incompatible type: $type, compatible types: string, array, Query");
        }

        $this->data = $data;
    }

    /**
     * Should return a quoted string with the operator and the condition part
     *
     * @param Connection $connection
     * @return string
     */
    public function getCondition(Connection $connection): string
    {
        if ($this->data instanceof Query) {
            return " IN ({$this->data->getSelectQuery()})";
        }
        if (is_array($this->data)) {
            $quotedString = $connection->quote($this->data);

            return " IN ($quotedString)";
        }

        return " IN ($this->data)";
    }
}
