<?php

/** @noinspection SqlResolve */

namespace Neat\Database\Test;

use Neat\Database\Connection;
use Neat\Database\ImmutableQueryBuilder;
use Neat\Database\MutableQueryBuilder;
use Neat\Database\QueryBuilder;
use PDO;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use TypeError;

/**
 * Factory
 *
 * @method MockBuilder getMockBuilder($className)
 */
trait Factory
{
    /**
     * Create PDO
     *
     * @return PDO
     */
    public function pdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (
                        id INTEGER PRIMARY KEY,
                        username TEXT
                    )');
        $pdo->exec("INSERT INTO users (id, username) VALUES
                    (1, 'john'),
                    (2, 'jane'),
                    (3, 'bob')");

        return $pdo;
    }

    /**
     * Create PDO mock
     *
     * @param array $methods (optional)
     * @return PDO|MockObject
     */
    public function mockedPdo(array $methods = [])
    {
        return $this
            ->getMockBuilder(PDO::class)
            ->onlyMethods($methods)
            ->setConstructorArgs(['sqlite::memory:'])
            ->getMock();
    }

    /**
     * Create connection
     *
     * @param PDO $pdo
     * @return Connection
     */
    public function connection($pdo = null)
    {
        if (!$pdo) {
            $pdo = $this->pdo();
        }

        return new Connection($pdo);
    }

    /**
     * Create connection mock
     *
     * @param PDO   $pdo
     * @param array $methods
     * @return Connection|MockObject
     */
    public function mockedConnection($pdo = null, $methods = [])
    {
        if (!$pdo) {
            $pdo = $this->pdo();
        }

        return $this
            ->getMockBuilder(Connection::class)
            ->setMethods($methods)
            ->setConstructorArgs([$pdo])
            ->getMock();
    }

    public function query(string $type, Connection $connection = null): QueryBuilder
    {
        $connection = $connection ?? $this->connection();
        if ($type === 'immutable') {
            return new ImmutableQueryBuilder($connection);
        } elseif ($type === 'mutable') {
            return new MutableQueryBuilder($connection);
        } else {
            throw new TypeError("Unknown query type: '$type'");
        }
    }
}
