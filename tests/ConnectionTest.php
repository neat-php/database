<?php

/** @noinspection SqlResolve */

namespace Neat\Database\Test;

use DateTime;
use DateTimeImmutable;
use Neat\Database\FetchedResult;
use Neat\Database\Query;
use Neat\Database\Result;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    use Factory;
    use SQLHelper;

    /**
     * Test getting/setting the PDO instance
     */
    public function testGetOrSetPDO()
    {
        $pdo1 = $this->pdo();
        $pdo2 = $this->pdo();

        $connection = $this->connection($pdo1);

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
        $connection = $this->connection();

        $this->assertSame('NULL', $connection->quote(null));
        $this->assertSame("'34'", $connection->quote(34));
        $this->assertSame("'bilbo'", $connection->quote("bilbo"));
        $this->assertSame("'''; --'", $connection->quote("'; --"));
        $this->assertSame("'2020-02-15 01:23:45'", $connection->quote(new DateTime('2020-02-15 01:23:45')));
        $this->assertSame("'2020-02-15 01:23:45'", $connection->quote(new DateTimeImmutable('2020-02-15 01:23:45')));
        $this->assertSame("'1','2','3'", $connection->quote([1, 2, 3]));
        $this->assertSame("'0'", $connection->quote(false));
        $this->assertSame("'1'", $connection->quote(true));
    }

    /**
     * Test quote parameter
     */
    public function testQuoteIdentifier()
    {
        $connection = $this->connection();

        $this->assertSame('`id`', $connection->quoteIdentifier('id'));
        $this->assertSame("`table`.`id`", $connection->quoteIdentifier('table.id'));
    }

    /**
     * Test merge parameters
     */
    public function testMergeParameters()
    {
        $connection = $this->connection();

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
        $connection = $this->connection();

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
        $connection = $this->connection();

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
        $connection = $this->connection();

        $query = function () use ($connection) {
            return $connection->query('SELECT * FROM users ORDER BY username');
        };

        $this->assertInstanceOf(Result::class, $query());
        $this->assertEquals([
            ['id' => '3', 'username' => 'bob'],
            ['id' => '2', 'username' => 'jane'],
            ['id' => '1', 'username' => 'john'],
        ], $query()->rows());
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
        $connection = $this->connection();

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

        $expected = [
            ['id' => '3', 'username' => 'bob'],
            ['id' => '2', 'username' => 'jane'],
            ['id' => '1', 'username' => 'john'],
        ];
        foreach ($connection->query('SELECT * FROM users ORDER BY username') as $username) {
            $this->assertEquals(array_shift($expected), $username);
        }

        $expected = [
            ['username' => 'bob'],
            ['username' => 'jane'],
            ['username' => 'john'],
        ];
        foreach ($connection->query('SELECT username FROM users ORDER BY username') as $username) {
            $this->assertEquals(array_shift($expected), $username);
        }

        $expected = [
            ['id' => '3', 'username' => 'bob'],
            ['id' => '2', 'username' => 'jane'],
            ['id' => '1', 'username' => 'john'],
        ];

        $result = $connection
            ->query('SELECT * FROM users ORDER BY username')
            ->each(function ($id, $username) use (&$expected) {
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
        $pdo        = $this->mockedPdo();
        $connection = $this->connection($pdo);

        /** @noinspection SqlWithoutWhere */
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

        /** @noinspection SqlWithoutWhere */
        $this->assertEquals(3, $connection->execute('DELETE FROM users'));
        $this->assertEquals(1, $connection->execute('DELETE FROM users WHERE id = ?', 1));
    }

    /**
     * Test execute
     */
    public function testInsertedId()
    {
        $pdo        = $this->mockedPdo(['lastInsertId']);
        $connection = $this->connection($pdo);

        $pdo->expects($this->at(0))
            ->method('lastInsertId')
            ->willReturn('4');

        $this->assertSame(4, $connection->insertedId());
    }

    /**
     * Test direct object invocation
     */
    public function testInvoke()
    {
        $connection = $this->connection();

        $this->assertSame(1, $connection("DELETE FROM users WHERE id=?", 1));
        $this->assertInstanceOf(Result::class, $connection("SELECT * FROM users"));
    }

    public function testSelect()
    {
        $connection = $this->connection();
        $select = $connection->select();

        $this->assertInstanceOf(Query::class, $select);
        $this->assertSame('*', $select->getSelect());

        $select = $connection->select('id');

        $this->assertInstanceOf(Query::class, $select);
        $this->assertSame('id', $select->getSelect());
    }

    public function testInsert()
    {
        $connection = $this->mockedConnection(null, ['execute']);
        $insert = $connection->insert('users');

        $this->assertInstanceOf(Query::class, $insert);
        $this->assertSame('`users`', $insert->getTable());

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->sql("INSERT INTO `users` (`username`) VALUES ('sam')"))
            ->willReturn(1);

        $this->assertEquals(
            1,
            $connection->insert('users', ['username' => 'sam'])
        );
    }

    public function testUpdate()
    {
        $connection = $this->mockedConnection(null, ['execute']);
        $update = $connection->update('users');

        $this->assertInstanceOf(Query::class, $update);
        $this->assertSame('`users`', $update->getTable());
        $this->assertSQL(
            "UPDATE `users`
             SET `active` = '0'",
            $update->set(['active' => 0])->getQuery()
        );
        $this->assertSQL(
            "UPDATE `users`
             SET `active` = '0'
             WHERE email LIKE '%@example.com'",
            $update->where('email LIKE ?', '%@example.com')->getQuery()
        );
        $this->assertSQL(
            "UPDATE `users`
             SET `active` = '0'
             WHERE email LIKE '%@example.com'
             ORDER BY id
             LIMIT 10",
            $update->orderBy('id')->limit(10)->getQuery()
        );

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->sql("UPDATE `users` SET `username`='sam' WHERE `id`='2'"))
            ->willReturn(1);

        $this->assertEquals(
            1,
            $connection->update('users', ['username' => 'sam'], ['id' => 2])
        );
    }

    public function testUpsert()
    {
        $connection = $this->mockedConnection(null, ['execute']);
        $upsert     = $connection->upsert('users');

        $this->assertInstanceOf(Query::class, $upsert);
        $this->assertSame('`users`', $upsert->getTable());

        /** @noinspection SqlResolve */
        $this->assertSQL(
            "INSERT INTO `users` (`id`, `username`)
             VALUES ('1', 'john')
             ON DUPLICATE KEY UPDATE
             `username` = 'john'",
            $upsert->values(['id' => 1, 'username' => 'john'])->set(['username' => 'john'])->getQuery()
        );


        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->sql("INSERT INTO `users` (`id`, `username`) VALUES ('1', 'john') ON DUPLICATE KEY UPDATE `username` = 'john'"))
            ->willReturn(1);

        $this->assertEquals(
            1,
            $connection->upsert('users', ['id' => 1, 'username' => 'john'], ['id'])
        );
    }

    public function testDelete()
    {
        $connection = $this->mockedConnection(null, ['execute']);
        $delete     = $connection->delete('users');

        $this->assertInstanceOf(Query::class, $delete);
        $this->assertSame('`users`', $delete->getTable());
        /** @noinspection SqlWithoutWhere */
        $this->assertSQL(
            'DELETE FROM `users`',
            $delete->getQuery()
        );
        $this->assertSQL(
            "DELETE FROM `users` WHERE id='3'",
            $delete->where('id = ?', 3)->getQuery()
        );
        $this->assertSQL(
            "DELETE FROM `users` WHERE id='3' LIMIT 1",
            $delete->limit(1)->getQuery()
        );

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->sql("DELETE FROM `users` WHERE `id`='3'"))
            ->willReturn(1);

        $this->assertEquals(
            1,
            $connection->delete('users', ['id' => 3])
        );
    }
}
