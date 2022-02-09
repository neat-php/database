<?php

namespace Neat\Database\Test;

use PHPUnit\Framework\Constraint\Callback;

/**
 * @method void assertEquals($expected, $actual)
 * @method Callback callback(callable $callback)
 */
trait SQLHelper
{
    /**
     * Minify SQL queries by removing unused whitespace
     *
     * @param string|null $query
     * @return string
     */
    protected function minifySQL(?string $query): string
    {
        if (!$query) {
            return '';
        }

        $replace = [
            '|\s+|m'     => ' ',
            '|\s*,\s*|m' => ',',
            '|\s*=\s*|m' => '=',
        ];

        return preg_replace(array_keys($replace), $replace, $query);
    }

    /**
     * Assert SQL matches expectation
     *
     * Normalizes whitespace to make the tests less fragile
     *
     * @param string|null $expected
     * @param string|null $actual
     */
    protected function assertSQL(?string $expected, ?string $actual)
    {
        $this->assertEquals(
            $this->minifySQL($expected),
            $this->minifySQL($actual)
        );
    }

    /**
     * SQL expectation constraint
     *
     * @param string $expected
     * @return callable|Callback
     */
    protected function sql(string $expected)
    {
        return $this->callback(function ($query) use ($expected) {
            return $this->minifySQL($query) == $this->minifySQL($expected);
        });
    }

}
