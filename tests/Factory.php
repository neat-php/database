<?php namespace Phrodo\Database\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Phrodo\Database\Connection;
use Phrodo\Database\Query;
use PDO;

/**
 * Factory
 */
class Factory
{
    /**
     * Test case
     *
     * @var TestCase
     */
    protected $case;

    /**
     * Constructor
     *
     * @param TestCase $case
     */
    public function __construct(TestCase $case)
    {
        $this->case = $case;
    }

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
     * @return PDO|Mock
     */
    public function mockedPdo($methods = [])
    {
        return $this->case
            ->getMockBuilder(PDO::class)
            ->setMethods($methods)
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
     * @return Connection|Mock
     */
    public function mockedConnection($pdo = null, $methods = [])
    {
        if (!$pdo) {
            $pdo = $this->pdo();
        }

        return $this->case
            ->getMockBuilder(Connection::class)
            ->setMethods($methods)
            ->setConstructorArgs([$pdo])
            ->getMock();
    }

    /**
     * Create query
     *
     * @param Connection $connection
     * @return Query
     */
    public function query($connection = null)
    {
        if (!$connection) {
            $connection = $this->connection();
        }

        return new Query($connection);
    }
}
