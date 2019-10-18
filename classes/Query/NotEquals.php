<?php

namespace Neat\Database\Query;

use Neat\Database\Connection;

class NotEquals implements ConditionInterface
{
    /**
     * @var \DateTime|int|null|string
     */
    private $value;

    /**
     * NotEquals constructor.
     * @param null|bool|int|string|\DateTimeInterface $value
     */
    public function __construct($value = null)
    {
        if (!$value instanceof \DateTimeInterface && !is_null($value) && !is_bool($value) && !is_int($value) && !is_string($value)){
            $type = gettype($value);
            throw new \TypeError("Incompatible type: $type, compatible types: null, bool, int, string, DateTime");
        }

        $this->value = $value;
    }

    /**
     * Should return a quoted string with the operator and the condition part
     *
     * @param Connection $connection
     * @return string
     */
    public function getCondition(Connection $connection): string
    {
        if (is_null($this->value)) {
            return " IS NOT NULL";
        }

        $quoted = $connection->quote($this->value);

        return " != $quoted";
    }
}
