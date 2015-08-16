<?php namespace Phrodo\Database\Test;

use Mockery;
use PDO;
use Phrodo\Database\Connection;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Get a PDO instance
     *
     * @return PDO
     */
    private function createPDO()
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
     * Get mocked PDO instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PDO
     */
    private function createMockPDO()
    {
        return $this->getMock('PDO', [], ['sqlite::memory:']);
    }

    /**
     * Get a PDO instance
     *
     * @param object $pdo
     * @return Connection
     */
    private function createConnection($pdo = null)
    {
        return new Connection($pdo ?: $this->createPDO());
    }

    /**
     * Test getting/setting the PDO instance
     */
    public function testGetOrSetPDO()
    {
        $pdo1 = $this->createPDO();
        $pdo2 = $this->createPDO();
        $connection = new Connection($pdo1);

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
        $connection = $this->createConnection();

        $this->assertSame('NULL', $connection->quote(null));
        $this->assertSame("'34'", $connection->quote(34));
        $this->assertSame("'bilbo'", $connection->quote("bilbo"));
        $this->assertSame("'''; --'", $connection->quote("'; --"));
        $this->assertSame("'2020-02-15 01:23:45'", $connection->quote(new \DateTime('2020-02-15 01:23:45')));
    }

    /**
     * Test merge parameters
     */
    public function testMergeParameters()
    {
        $connection = $this->createConnection();

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
            $connection->merge('SELECT stuff WHERE foo=? AND bar=?', [1, 3, 5])
        );
        $this->assertEquals(
            "SELECT stuff WHERE foo='1' AND bar=?",
            $connection->merge('SELECT stuff WHERE foo=? AND bar=?', [1])
        );
    }

    /**
     * Test query
     */
    public function testQuery()
    {
        $connection = $this->createConnection();

        $result = $connection->query('SELECT username FROM users WHERE id = 1');
        $this->assertInstanceOf('Phrodo\Database\Result', $result);
        $this->assertEquals([['username' => 'john']], $result->rows());

        $result = $connection->query('SELECT username FROM users WHERE id = ?', 1);
        $this->assertInstanceOf('Phrodo\Database\Result', $result);
        $this->assertEquals([['username' => 'john']], $result->rows());
    }

    /**
     * Test query result
     */
    public function testQueryResult()
    {
        $connection = $this->createConnection();

        $query = function () use ($connection) {
            return $connection->query('SELECT * FROM users ORDER BY username');
        };

        $this->assertInstanceOf('Phrodo\Database\Result', $query());
        $this->assertEquals([['id' => '3', 'username' => 'bob'], ['id' => '2', 'username' => 'jane'], ['id' => '1', 'username' => 'john']], $query()->rows());
        $this->assertEquals(['id' => '3', 'username' => 'bob'], $query()->row());
        $this->assertEquals([3, 2, 1], $query()->values());
        $this->assertEquals(['bob', 'jane', 'john'], $query()->values(1));
        $this->assertEquals(3, $query()->value());
        $this->assertEquals('bob', $query()->value(1));
    }

    public function testTraverse()
    {
        $connection = $this->createConnection();

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

    public function testQueryBuilder()
    {
        $connection = $this->createConnection();

        $select = $connection->select();
        $this->assertInstanceOf('Phrodo\Database\Query', $select);
    }

    public function testExecute()
    {
        $pdo        = $this->createMockPDO();
        $connection = $this->createConnection($pdo);

        $pdo->expects($this->exactly(2))
            ->method('exec')
            ->willReturnMap([
                ['DELETE FROM users', 3],
                ["DELETE FROM users WHERE id = '1'", 1],
            ]);
        $pdo->expects($this->once())
            ->method('quote')
            ->willReturnMap([
                [1, null, "'1'"],
                ['bilbo', null, "'bilbo'"],
            ]);

        $this->assertEquals(3, $connection->execute('DELETE FROM users'));
        $this->assertEquals(1, $connection->execute('DELETE FROM users WHERE id = ?', 1));
    }

    public function testInsert()
    {
        $pdo        = $this->createMockPDO();
        $connection = $this->createConnection($pdo);
        $query      = $connection->insert('users');

        $this->assertInstanceOf('Phrodo\Database\Query', $query);
        $this->assertSame('users', $query->getTable());

        $pdo->expects($this->once())
            ->method('exec')
            ->with($this->callback(function ($query) {
                return preg_replace('/\s+/', ' ', $query) == "INSERT INTO users (username) VALUES ('bilbo')";
            }))
            ->willReturn(1);
        $pdo->expects($this->once())
            ->method('quote')
            ->with('bilbo')
            ->willReturn("'bilbo'");

        $this->assertEquals(1, $connection->insert('users', ['username' => 'bilbo']));
    }

    public function testUpdate()
    {
        $pdo        = $this->createMockPDO();
        $connection = $this->createConnection($pdo);
        $query      = $connection->update('users');

        $this->assertInstanceOf('Phrodo\Database\Query', $query);
        $this->assertSame('users', $query->getTable());

        $pdo->expects($this->exactly(2))
            ->method('quote')
            ->willReturnMap([['pippin', null, "'pippin'"], [2, null, "'2'"]]);
        $pdo->expects($this->once())
            ->method('exec')
            ->with($this->callback(function ($query) {
                return preg_replace('/\s+/', ' ', $query) == "UPDATE users SET username='pippin' WHERE id='2'";
            }))
            ->willReturn(1);

        $this->assertEquals(1, $connection->update('users', ['username' => 'pippin'], ['id' => 2]));
    }

    public function testDelete()
    {
        $pdo        = $this->createMockPDO();
        $connection = $this->createConnection($pdo);
        $query      = $connection->delete('users');

        $this->assertInstanceOf('Phrodo\Database\Query', $query);
        $this->assertSame('users', $query->getTable());

        $pdo->expects($this->once())
            ->method('quote')
            ->with(1)
            ->willReturn("'1'");
        $pdo->expects($this->once())
            ->method('exec')
            ->with($this->callback(function ($query) {
                return preg_replace('/\s+/', ' ', $query) == "DELETE FROM users WHERE id='1'";
            }))
            ->willReturn(1);

        $this->assertEquals(1, $connection->delete('users', ['id' => 1]));
    }

    /**
     * Test direct object invocation
     */
    function testInvoke()
    {
        $connection = $this->createConnection();
        $this->assertSame(1, $connection("DELETE FROM users WHERE id=?", 1));
        $this->assertInstanceOf('Phrodo\Database\Result', $connection("SELECT * FROM users"));
    }
}
