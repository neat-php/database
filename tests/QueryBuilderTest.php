<?php
/** @noinspection SqlResolve */

namespace Neat\Database\Test;

use Neat\Database\FetchedResult;
use Neat\Database\Query;
use Neat\Database\SQLQuery;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    use Factory;
    use SQLHelper;

    /**
     * @return array
     */
    public function provideQuery(): array
    {
        return [
            'immutable' => [$this->query('immutable', null)],
            'mutable' => [$this->query('mutable', null)],
        ];
    }

    public function provideType()
    {
        return [
            'immutable' => ['immutable'],
            'mutable' => ['mutable'],
        ];
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testSelectExpressions(string $type)
    {
        $query = $this->query($type, null);
        $this->assertEquals(
            '*',
            $query->select()->getSelect()
        );

        $query = $this->query($type, null);
        $this->assertEquals(
            'id',
            $query->select('id')->getSelect()
        );

        $query = $this->query($type, null);
        $this->assertEquals(
            'id,username',
            $query->select('id')->select(['username'])->getSelect()
        );

        $query = $this->query($type, null);
        $this->assertEquals(
            'id,username,COUNT(*) AS amount',
            $query->select('id')->select(['username'])->select(['amount' => 'COUNT(*)'])->getSelect()
        );

        $query = $this->query($type, null);
        $this->assertEquals(
            'id,MIN(price) AS min_price',
            $query->select(['id', 'min_price' => 'MIN(price)'])->getSelect()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testFrom(string $type)
    {
        $this->assertEquals(
            '`users`',
            $this->query($type, null)
                ->from('users')
                ->getFrom()
        );
        $this->assertEquals(
            '`users` u',
            $this->query($type, null)
                ->from('users', 'u')
                ->getFrom()
        );
        $this->assertEquals(
            '`users`,`groups`',
            $this->query($type, null)
                ->from(['users', 'groups'])
                ->getFrom()
        );
        $this->assertEquals(
            '`users` u,`groups` g',
            $this->query($type, null)
                ->from(['u' => 'users', 'g' => 'groups'])
                ->getFrom()
        );

        $this->assertEquals(
            '(SELECT * FROM dual) d',
            $this->query($type, null)
                ->from(new SQLQuery($this->connection(), 'SELECT * FROM dual'), 'd')
                ->getFrom()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testInto(string $type)
    {
        $this->assertEquals(
            '`users`',
            $this->query($type, null)
                ->into('users')
                ->getTable()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testJoin(string $type)
    {
        $this->assertSQL(
            '`users` u INNER JOIN (SELECT id FROM `teams`) ON u.team_id',
            $this->query($type, null)
                ->from('users', 'u')
                ->join($this->query($type, null)->select('id')->from('teams'), null, 'u.team_id')
                ->getFrom()
        );
        $this->assertSQL(
            '`users` u INNER JOIN `teams` t ON u.team_id = t.id',
            $this->query($type, null)
                ->from('users', 'u')
                ->innerJoin('teams', 't', 'u.team_id = t.id')
                ->getFrom()
        );
        $this->assertSQL(
            '`users` u RIGHT JOIN `teams` t ON u.team_id = t.id',
            $this->query($type, null)
                ->from('users', 'u')
                ->rightJoin('teams', 't', 'u.team_id = t.id')
                ->getFrom()
        );
        $this->assertSQL(
            '`users` u
             LEFT JOIN `users_groups` ug ON u.id = ug.user_id
             LEFT JOIN `groups` g ON g.id = ug.group_id',
            $this->query($type, null)
                ->from('users', 'u')
                ->leftJoin('users_groups', 'ug', 'u.id = ug.user_id')
                ->leftJoin('groups', 'g', 'g.id = ug.group_id')
                ->getFrom()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testWhere(string $type)
    {
        $this->assertSQL(
            "`username`='john'",
            $this->query($type, null)
                ->where(['username' => 'john'])
                ->getWhere()
        );
        $this->assertSQL(
            "`deleted_at` IS NULL",
            $this->query($type, null)
                ->where(['deleted_at' => null])
                ->getWhere()
        );
        $this->assertSQL(
            "`id` IN ('1','2','3')",
            $this->query($type, null)
                ->where(['id' => [1, 2, 3]])
                ->getWhere()
        );
        $this->assertSQL(
            "username='john' AND email='john@example.com'",
            $this->query($type, null)
                ->where('username=? AND email=?', 'john', 'john@example.com')
                ->getWhere()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testGroupBy(string $type)
    {
        $this->assertSQL(
            '',
            $this->query($type, null)->getGroupBy()
        );
        $this->assertSQL(
            'id',
            $this->query($type, null)->groupBy('id')->getGroupBy()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testHaving(string $type)
    {
        $this->assertSQL(
            "COUNT(*) = '3'",
            $this->query($type, null)->having(['COUNT(*)' => 3])->getHaving()
        );
        $this->assertSQL(
            "COUNT(*) > '1'",
            $this->query($type, null)->having('COUNT(*) > ?', 1)->getHaving()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testOrderBy(string $type)
    {
        $this->assertSQL(
            '',
            $this->query($type, null)->getOrderBy()
        );
        $this->assertSQL(
            'date ASC',
            $this->query($type, null)->orderBy('date ASC')->getOrderBy()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testLimit(string $type)
    {
        $this->assertSame(
            '',
            $this->query($type, null)->getLimit()
        );
        $this->assertSame(
            '10',
            $this->query($type, null)->limit(10)->getLimit()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testOffset(string $type)
    {
        $this->assertSame(
            '',
            $this->query($type, null)->offset(20)->getLimit()
        );
        $this->assertSame(
            '20,10',
            $this->query($type, null)->limit(10)->offset(20)->getLimit()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testSelectBuilder(string $type)
    {
        $this->assertSQL(
            "SELECT 1 FROM `dual`",
            $this->query($type, null)->select(1)->from('dual')->getQuery()
        );
        $this->assertSQL(
            "SELECT g.*, COUNT(1) AS active_users
             FROM `users` u
             LEFT JOIN `users_groups` ug ON u.id = ug.user_id
             LEFT JOIN `groups` g ON g.id = ug.group_id
             WHERE users.active = '1'
             GROUP BY g.id
             HAVING COUNT(u.id) > 1
             ORDER BY g.name
             LIMIT 25",
            $this->query($type, null)
                ->select('g.*, COUNT(1) AS active_users')
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
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testSelectQuery(string $type)
    {
        $connection = $this->mockedConnection(null, ['query']);
        $connection
            ->expects($this->once())
            ->method('query')
            ->with($this->sql("SELECT * FROM `users` WHERE id = '1'"))
            ->willReturn($result = new FetchedResult([['id' => 1, 'username' => 'john']]));

        $this->assertSame(
            $result,
            $this->query($type, $connection)->select()->from('users')->where('id = ?', 1)->query()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testSelectFetch(string $type)
    {
        $connection = $this->mockedConnection(null, ['fetch']);
        $connection
            ->expects($this->once())
            ->method('fetch')
            ->with($this->sql("SELECT * FROM `users` WHERE id = '1'"))
            ->willReturn($result = new FetchedResult([['id' => 1, 'username' => 'john']]));

        $this->assertSame(
            $result,
            $this->query($type, $connection)->select()->from('users')->where('id = ?', 1)->fetch()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testInsertBuilder(string $type)
    {
        $connection = $this->mockedConnection(null, ['execute']);
        $insert = $this->query($type, $connection)->insert('users');

        $this->assertSame('`users`', $insert->getTable());

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->sql("INSERT INTO `users` (`username`) VALUES ('sam')"))
            ->willReturn(1);

        $this->assertEquals(
            1,
            $this->query($type, $connection)->insert('users')->values(['username' => 'sam'])->execute()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testUpdateBuilder(string $type)
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
            $this->query($type, $connection)->update('users')->set(['username' => 'sam'])->where(['id' => 2])->execute()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testUpsertBuilder(string $type)
    {
        $connection = $this->mockedConnection(null, ['execute']);
        $upsert = $this->query($type, $connection)->upsert('users');

        $this->assertSame('`users`', $upsert->getTable());

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
            ->with(
                $this->sql(
                    "INSERT INTO `users` (`id`, `username`) VALUES ('1', 'john') ON DUPLICATE KEY UPDATE `username` = 'john'"
                )
            )
            ->willReturn(1);

        $this->assertEquals(
            1,
            $this->query($type, $connection)
                ->upsert('users')
                ->values(['id' => 1, 'username' => 'john'])
                ->set(['username' => 'john'])
                ->where(['id' => 1])
                ->execute()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testDeleteBuilder(string $type)
    {
        $connection = $this->mockedConnection(null, ['execute']);
        $delete = $this->query($type, $connection)->delete('users');

        $this->assertSame('`users`', $delete->getTable());
        /** @noinspection SqlWithoutWhere */
        $this->assertSQL(
            'DELETE FROM `users`',
            $delete->getQuery()
        );
        $delete = $delete->where('id = ?', 3);
        $this->assertSQL(
            "DELETE FROM `users` WHERE id='3'",
            $delete->getQuery()
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
            $this->query($type, $connection)->delete('users')->where(['id' => 3])->execute()
        );
    }

    /**
     * @dataProvider provideType
     * @param string $type
     * @return void
     */
    public function testGetQuery(string $type)
    {
        $this->assertSQL(
            "SELECT 1 FROM `dual`",
            $this->query($type)
                ->select(1)
                ->from('dual')
                ->getQuery()
        );
        $this->assertSQL(
            "SELECT 1 FROM `dual`",
            (string)$this->query($type)
                ->select(1)
                ->from('dual')
        );

        $this->expectException('RuntimeException');
        $query = $this->query($type);
        $query->getQuery();
    }
}
