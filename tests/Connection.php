<?php namespace Phrodo\Database\Test;

use Mockery;
use PDO;

class Connection extends \PHPUnit_Framework_TestCase
{

    /**
     * Get a PDO instance
     *
     * @return PDO
     */
    private function getPDO()
    {
        $pdo = new PDO('sqlite::memory:');
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
     * Get a PDO instance
     *
     * @return \Phrodo\Database\Connection
     */
    private function getConnection()
    {
        return new \Phrodo\Database\Connection($this->getPDO());
    }

    /**
     * Test getting/setting the PDO instance
     */
    public function testGetOrSetPDO()
    {
        $pdo1 = $this->getPDO();
        $pdo2 = $this->getPDO();
        $connection = new \Phrodo\Database\Connection($pdo1);

        $this->assertInstanceOf('PDO', $connection->pdo());
        $this->assertSame($pdo1, $connection->pdo());
        $this->assertSame($pdo2, $connection->pdo($pdo2));
        $this->assertSame($pdo2, $connection->pdo());
    }

    /**
     * Test quote parameter
     */
    public function testQuoteParameter()
    {
        $connection = $this->getConnection();

        $this->assertSame('NULL', $connection->quote(null));
        $this->assertSame("'34'", $connection->quote(34));
        $this->assertSame("'''; --'", $connection->quote("'; --"));
        $this->assertSame("'2020-02-15 01:23:45'", $connection->quote(new \DateTime('2020-02-15 01:23:45')));
    }

    /**
     * Test merge parameters
     */
    public function testMergeParameters()
    {
        $connection = $this->getConnection();

        $this->assertEquals(
            'SELECT stuff',
            $connection->merge('SELECT stuff')
        );
        $this->assertEquals(
            'SELECT stuff',
            $connection->merge('SELECT stuff', [])
        );
        $this->assertEquals(
            "SELECT stuff WHERE foo='1' AND bar='3'",
            $connection->merge('SELECT stuff WHERE foo=? AND bar=?', [1, 3])
        );
        $this->assertEquals(
            "SELECT stuff WHERE foo='1' AND bar='3'",
            $connection->merge('SELECT stuff WHERE foo=? AND bar=?', 1, 3)
        );
        $this->assertEquals(
            "SELECT stuff WHERE foo='1' AND bar='3'",
            $connection->merge('SELECT stuff WHERE foo=? AND bar=?', [1, 3, 5])
        );
    }

    /**
     * Test merge with missing parameters
     */
    public function testMergeMissingParameters()
    {
        $connection = $this->getConnection();

        $this->setExpectedException('RuntimeException');
        $connection->merge('SELECT stuff WHERE foo=? AND bar=?', 1);
    }

    /**
     * Test query
     */
    public function testQuery()
    {
        $connection = $this->getConnection();

        $query = function () use ($connection) {
            return $connection->query('SELECT * FROM users ORDER BY username');
        };

        $this->assertInstanceOf('Phrodo\Database\Contract\Result', $query());
        $this->assertEquals([['id' => '3', 'username' => 'bob'], ['id' => '2', 'username' => 'jane'], ['id' => '1', 'username' => 'john']], $query()->rows());
        $this->assertEquals(['id' => '3', 'username' => 'bob'], $query()->row());
        $this->assertEquals([3, 2, 1], $query()->values());
        $this->assertEquals(['bob', 'jane', 'john'], $query()->values(1));
        $this->assertEquals(3, $query()->value());
        $this->assertEquals('bob', $query()->value(1));
    }

    public function testTraverse()
    {
        $connection = $this->getConnection();

        $result = $connection->query('SELECT * FROM users ORDER BY username');
        $this->assertEquals(['id' => '3', 'username' => 'bob'], $result->row());
        $this->assertEquals(['id' => '2', 'username' => 'jane'], $result->row());
        $this->assertEquals(['id' => '1', 'username' => 'john'], $result->row());
        $this->assertFalse($result->row());

        $result = $connection->query('SELECT username FROM users ORDER BY username');
        $this->assertEquals('bob', $result->value());
        $this->assertEquals('jane', $result->value());
        $this->assertEquals('john', $result->value());
        $this->assertFalse($result->value());

        $expected = [['id' => '3', 'username' => 'bob'], ['id' => '2', 'username' => 'jane'], ['id' => '1', 'username' => 'john']];
        foreach ($connection->query('SELECT * FROM users ORDER BY username') as $username) {
            $this->assertEquals(array_shift($expected), $username);
        }

        $expected = ['bob', 'jane', 'john'];
        foreach ($connection->query('SELECT username FROM users ORDER BY username') as $username) {
            $this->assertEquals(array_shift($expected), $username);
        }

        $expected = [['id' => '3', 'username' => 'bob'], ['id' => '2', 'username' => 'jane'], ['id' => '1', 'username' => 'john']];
        $connection->query('SELECT * FROM users ORDER BY username')->each(function ($id, $username) use (&$expected) {
            $this->assertEquals(array_shift($expected), ['id' => $id, 'username' => $username]);
        });
    }

    //
    //public function testCount()
    //{
    //    //$this->assertEquals(3, $query()->count());
    //    //$this->assertEquals(3, count($query()));
    //}

}
