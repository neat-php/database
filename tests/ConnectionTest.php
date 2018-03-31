<?php
namespace Neat\Database\Test;

use DateTime;
use Neat\Database\FetchedResult;
use Neat\Database\Result;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    /**
     * Factory
     *
     * @var Factory
     */
    private $create;

    /**
     * Setup factory
     */
    public function setup()
    {
        $this->create = new Factory($this);
    }

    /**
     * Test getting/setting the PDO instance
     */
    public function testGetOrSetPDO()
    {
        $pdo1 = $this->create->pdo();
        $pdo2 = $this->create->pdo();
        $connection = $this->create->connection($pdo1);

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
        $connection = $this->create->connection();

        $this->assertSame('NULL', $connection->quote(null));
        $this->assertSame("'34'", $connection->quote(34));
        $this->assertSame("'bilbo'", $connection->quote("bilbo"));
        $this->assertSame("'''; --'", $connection->quote("'; --"));
        $this->assertSame("'2020-02-15 01:23:45'", $connection->quote(new DateTime('2020-02-15 01:23:45')));
        $this->assertSame("'1','2','3'", $connection->quote([1, 2, 3]));
    }

    /**
     * Test quote parameter
     */
    public function testQuoteIdentifier()
    {
        $connection = $this->create->connection();

        $this->assertSame('`id`', $connection->quoteIdentifier('id'));
        $this->assertSame("`table`.`id`", $connection->quoteIdentifier('table.id'));
    }

    /**
     * Test merge parameters
     */
    public function testMergeParameters()
    {
        $connection = $this->create->connection();

        $this->assertEquals(
            'SELECT stuff',
            $connection->merge('SELECT stuff', [])
        );
        $this->assertEquals(
            "WHERE foo='1' AND bar='3'",
            $connection->merge('WHERE foo=? AND bar=?', [1, 3])
        );
        $this->assertEquals(
            "WHERE foo='?' AND bar='1'",
            $connection->merge("WHERE foo='?' AND bar=?", [1, 3])
        );
        $this->assertEquals(
            "WHERE foo='1' AND bar='3'",
            $connection->merge('WHERE foo=? AND bar=?', [1, 3, 5])
        );
        $this->assertEquals(
            "WHERE foo='1' AND bar=?",
            $connection->merge('WHERE foo=? AND bar=?', [1])
        );
        $stamp = '2017-07-10 17:18:19';
        $this->assertEquals(
            "WHERE stamp > '$stamp'",
            $connection->merge('WHERE stamp > ?', [new DateTime($stamp)])
        );
        $this->assertEquals(
            'VALUES (NULL)',
            $connection->merge('VALUES (?)', [null])
        );
        $this->assertEquals(
            "IN ('1','2','3')",
            $connection->merge('IN (?)', [[1, 2, 3]])
        );
    }

    /**
     * Test query
     */
    public function testQuery()
    {
        $connection = $this->create->connection();

        $result = $connection->query('SELECT username FROM users WHERE id = 1');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals([['username' => 'john']], $result->rows());

        $result = $connection->query('SELECT username FROM users WHERE id = ?', 1);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals([['username' => 'john']], $result->rows());
    }

    /**
     * Test fetch
     */
    public function testFetch()
    {
        $connection = $this->create->connection();

        $result = $connection->fetch('SELECT username FROM users WHERE id = 1');
        $this->assertInstanceOf(FetchedResult::class, $result);
        $this->assertEquals([['username' => 'john']], $result->rows());

        $result = $connection->fetch('SELECT username FROM users WHERE id = ?', 1);
        $this->assertInstanceOf(FetchedResult::class, $result);
        $this->assertEquals([['username' => 'john']], $result->rows());
    }

    /**
     * Test query result
     */
    public function testQueryResult()
    {
        $connection = $this->create->connection();

        $query = function () use ($connection) {
            return $connection->query('SELECT * FROM users ORDER BY username');
        };

        $this->assertInstanceOf(Result::class, $query());
        $this->assertEquals([['id' => '3', 'username' => 'bob'], ['id' => '2', 'username' => 'jane'], ['id' => '1', 'username' => 'john']], $query()->rows());
        $this->assertEquals(['id' => '3', 'username' => 'bob'], $query()->row());
        $this->assertEquals([3, 2, 1], $query()->values());
        $this->assertEquals(['bob', 'jane', 'john'], $query()->values(1));
        $this->assertEquals([3, 2, 1], $query()->values('id'));
        $this->assertEquals(['bob', 'jane', 'john'], $query()->values('username'));
        $this->assertEquals(3, $query()->value());
        $this->assertEquals('bob', $query()->value(1));
        $this->assertEquals(3, $query()->value('id'));
        $this->assertEquals('bob', $query()->value('username'));
    }

    /**
     * Test result traversal
     */
    public function testTraverse()
    {
        $connection = $this->create->connection();

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

        $expected = [['username' => 'bob'], ['username' => 'jane'], ['username' => 'john']];
        foreach ($connection->query('SELECT username FROM users ORDER BY username') as $username) {
            $this->assertEquals(array_shift($expected), $username);
        }

        $expected = [['id' => '3', 'username' => 'bob'], ['id' => '2', 'username' => 'jane'], ['id' => '1', 'username' => 'john']];
        $result = $connection->query('SELECT * FROM users ORDER BY username')->each(function ($id, $username) use (&$expected) {
            $this->assertEquals(array_shift($expected), ['id' => $id, 'username' => $username]);

            return "$id:$username";
        });
        $this->assertEquals(['3:bob', '2:jane', '1:john'], $result);
    }

    /**
     * Test execute
     */
    public function testExecute()
    {
        $pdo        = $this->create->mockedPdo();
        $connection = $this->create->connection($pdo);

        $pdo->expects($this->exactly(2))
            ->method('exec')
            ->willReturnMap([
                ['DELETE FROM users', 3],
                ["DELETE FROM users WHERE id = '1'", 1],
            ]);
        $pdo->expects($this->once())
            ->method('quote')
            ->with(1)
            ->willReturn("'1'");

        $this->assertEquals(3, $connection->execute('DELETE FROM users'));
        $this->assertEquals(1, $connection->execute('DELETE FROM users WHERE id = ?', 1));
    }

    /**
     * Test execute
     */
    public function testInsertedId()
    {
        $pdo        = $this->create->mockedPdo(['lastInsertId']);
        $connection = $this->create->connection($pdo);

        $pdo->expects($this->at(0))
            ->method('lastInsertId')
            ->willReturn('4');

        $this->assertSame(4, $connection->insertedId());
    }

    /**
     * Test direct object invocation
     */
    function testInvoke()
    {
        $connection = $this->create->connection();

        $this->assertSame(1, $connection("DELETE FROM users WHERE id=?", 1));
        $this->assertInstanceOf(Result::class, $connection("SELECT * FROM users"));
    }
}
