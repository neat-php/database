<?php

namespace Neat\Database\Test;

use Neat\Database\FetchedResult;
use Neat\Database\Query;
use Neat\Database\SQLQuery;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    use Factory;
    use SQLHelper;

    /**
     * Test select expressions
     */
    public function testSelectExpressions()
    {
        $query = $this->query();
        $this->assertEquals(
            '*',
            $query->select()->getSelect()
        );

        $query = $this->query();
        $this->assertEquals(
            'id',
            $query->select('id')->getSelect()
        );
        $this->assertEquals(
            'id,username',
            $query->select(['username'])->getSelect()
        );
        $this->assertEquals(
            'id,username,COUNT(*) AS amount',
            $query->select(['amount' => 'COUNT(*)'])->getSelect()
        );

        $query = $this->query();
        $this->assertEquals(
            'id,MIN(price) AS min_price',
            $query->select(['id', 'min_price' => 'MIN(price)'])->getSelect()
        );
    }

    /**
     * Test from
     */
    public function testFrom()
    {
        $this->assertEquals(
            '`users`',
            $this->query()
                ->from('users')
                ->getFrom()
        );
        $this->assertEquals(
            '`users` u',
            $this->query()
                ->from('users', 'u')
                ->getFrom()
        );
        $this->assertEquals(
            '`users`,`groups`',
            $this->query()
                ->from(['users', 'groups'])
                ->getFrom()
        );
        $this->assertEquals(
            '`users` u,`groups` g',
            $this->query()
                ->from(['u' => 'users', 'g' => 'groups'])
                ->getFrom()
        );

        $this->assertEquals(
            '(SELECT * FROM dual) d',
            $this->query()
                ->from(new SQLQuery($this->connection(), 'SELECT * FROM dual'), 'd')
                ->getFrom()
        );
    }

    /**
     * Test into
     */
    public function testInto()
    {
        $this->assertEquals(
            '`users`',
            $this->query()
                ->into('users')
                ->getTable()
        );
    }

    /**
     * Test join
     */
    public function testJoin()
    {
        $this->assertSQL(
            '`users` u INNER JOIN `teams` t ON u.team_id = t.id',
            $this->query()
                ->from('users', 'u')
                ->innerJoin('teams', 't', 'u.team_id = t.id')
                ->getFrom()
        );
        $this->assertSQL(
            '`users` u RIGHT JOIN `teams` t ON u.team_id = t.id',
            $this->query()
                ->from('users', 'u')
                ->rightJoin('teams', 't', 'u.team_id = t.id')
                ->getFrom()
        );
        $this->assertSQL(
            '`users` u
             LEFT JOIN `users_groups` ug ON u.id = ug.user_id
             LEFT JOIN `groups` g ON g.id = ug.group_id',
            $this->query()
                ->from('users', 'u')
                ->leftJoin('users_groups', 'ug', 'u.id = ug.user_id')
                ->leftJoin('groups', 'g', 'g.id = ug.group_id')
                ->getFrom()
        );
    }

    /**
     * Test where
     */
    public function testWhere()
    {
        $this->assertSQL(
            "`username`='john'",
            $this->query()
                ->where(['username' => 'john'])
                ->getWhere()
        );
        $this->assertSQL(
            "`deleted_at` IS NULL",
            $this->query()
                ->where(['deleted_at' => null])
                ->getWhere()
        );
        $this->assertSQL(
            "username='john' AND email='john@example.com'",
            $this->query()
                ->where('username=? AND email=?', 'john', 'john@example.com')
                ->getWhere()
        );
    }

    /**
     * Test group by
     */
    public function testGroupBy()
    {
        $this->assertSQL(
            '',
            $this->query()->getGroupBy()
        );
        $this->assertSQL(
            'id',
            $this->query()->groupBy('id')->getGroupBy()
        );
    }

    /**
     * Test having
     */
    public function testHaving()
    {
        $this->assertSQL(
            "COUNT(*) = '3'",
            $this->query()->having(['COUNT(*)' => 3])->getHaving()
        );
        $this->assertSQL(
            "COUNT(*) > '1'",
            $this->query()->having('COUNT(*) > ?', 1)->getHaving()
        );
    }

    /**
     * Test order by
     */
    public function testOrderBy()
    {
        $this->assertSQL(
            '',
            $this->query()->getOrderBy()
        );
        $this->assertSQL(
            'date ASC',
            $this->query()->orderBy('date ASC')->getOrderBy()
        );
    }

    /**
     * Test limit
     */
    public function testLimit()
    {
        $this->assertSame(
            '',
            $this->query()->getLimit()
        );
        $this->assertSame(
            '10',
            $this->query()->limit(10)->getLimit()
        );
    }

    /**
     * Test offset
     */
    public function testOffset()
    {
        $this->assertSame(
            '',
            $this->query()->offset(20)->getLimit()
        );
        $this->assertSame(
            '20,10',
            $this->query()->limit(10)->offset(20)->getLimit()
        );
    }

    /**
     * Test select
     */
    public function testSelectBuilder()
    {
        $connection = $this->mockedConnection(null, ['query']);
        $select     = $connection->select();

        $this->assertInstanceOf(Query::class, $select);
        $this->assertSQL(
            "SELECT 1 FROM `dual`",
            $connection->select(1)->from('dual')->getQuery()
        );
        /** @noinspection SqlResolve */
        $this->assertSQL(
            "SELECT g.*, COUNT(1) as active_users
             FROM `users` u
             LEFT JOIN `users_groups` ug ON u.id = ug.user_id
             LEFT JOIN `groups` g ON g.id = ug.group_id
             WHERE users.active = '1'
             GROUP BY g.id
             HAVING COUNT(u.id) > 1
             ORDER BY g.name
             LIMIT 25",
            $connection
                ->select('g.*, COUNT(1) as active_users')
                ->from('users', 'u')
                ->leftJoin('users_groups', 'ug', 'u.id = ug.user_id')
                ->leftJoin('groups', 'g', 'g.id = ug.group_id')
                ->where('users.active = ?', 1)
                ->groupBy('g.id')
                ->having('COUNT(u.id) > 1')
                ->orderBy('g.name')
                ->limit(25)
                ->getQuery()
        );
    }

    /**
     * Test select query
     */
    public function testSelectQuery()
    {
        $connection = $this->mockedConnection(null, ['query']);
        $connection
            ->expects($this->once())
            ->method('query')
            ->with($this->sql("SELECT * FROM `users` WHERE id = '1'"))
            ->willReturn($result = new FetchedResult([['id' => 1, 'username' => 'john']]));

        $this->assertSame(
            $result,
            $connection->select()->from('users')->where('id = ?', 1)->query()
        );
    }

    /**
     * Test select query
     */
    public function testSelectFetch()
    {
        $connection = $this->mockedConnection(null, ['fetch']);
        $connection
            ->expects($this->once())
            ->method('fetch')
            ->with($this->sql("SELECT * FROM `users` WHERE id = '1'"))
            ->willReturn($result = new FetchedResult([['id' => 1, 'username' => 'john']]));

        $this->assertSame(
            $result,
            $connection->select()->from('users')->where('id = ?', 1)->fetch()
        );
    }

    /**
     * Test insert
     */
    public function testInsertBuilder()
    {
        $connection = $this->mockedConnection(null, ['execute']);
        $insert     = $connection->insert('users');

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

    /**
     * Test update
     */
    public function testUpdateBuilder()
    {
        $connection = $this->mockedConnection(null, ['execute']);
        $update     = $connection->update('users');

        $this->assertInstanceOf(Query::class, $update);
        $this->assertSame('`users`', $update->getTable());
        /** @noinspection SqlResolve */
        $this->assertSQL(
            "UPDATE `users`
             SET `active` = '0'",
            $update->set(['active' => 0])->getQuery()
        );
        /** @noinspection SqlResolve */
        $this->assertSQL(
            "UPDATE `users`
             SET `active` = '0'
             WHERE email LIKE '%@example.com'",
            $update->where('email LIKE ?', '%@example.com')->getQuery()
        );
        /** @noinspection SqlResolve */
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

    /**
     * Test upsert
     */
    public function testUpsertBuilder()
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

    /**
     * Test delete
     */
    public function testDeleteBuilder()
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

    /**
     * Test get query
     */
    public function testGetQuery()
    {
        $connection = $this->connection();

        $this->assertSQL(
            "SELECT 1 FROM `dual`",
            $connection
                ->select(1)
                ->from('dual')
                ->getQuery()
        );
        $this->assertSQL(
            "SELECT 1 FROM `dual`",
            (string) $connection
                ->select(1)
                ->from('dual')
        );

        $this->expectException('RuntimeException');
        $query = new Query($connection);
        $query->getQuery();
    }
}
