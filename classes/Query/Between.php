<?php

namespace Neat\Database\Query;

use Neat\Database\Connection;

class Between implements ConditionInterface
{
    /**
     * @var \DateTimeInterface|int
     */
    private $min;

    /**
     * @var \DateTimeInterface|int
     */
    private $max;

    /**
     * Between constructor.
     * @param int|\DateTimeInterface $min
     * @param int|\DateTimeInterface $max
     */
    public function __construct($min, $max)
    {
        if ((!$min instanceof \DateTimeInterface && !is_int($min)) ||
            (!$max instanceof \DateTimeInterface && !is_int($max))
        ) {
            $typeMin = gettype($min);
            $typeMax = gettype($max);
            throw new \TypeError("Incompatible min: $typeMin or max: $typeMax, compatible types: int, DateTime");
        }

        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Should return a quoted string with the operator and the condition part
     *
     * @param Connection $connection
     * @return string
     */
    public function getCondition(Connection $connection): string
    {
        $min = $connection->quote($this->min);
        $max = $connection->quote($this->max);

        return " BETWEEN $min AND $max";
    }
}
