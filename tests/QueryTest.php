<?php namespace Phrodo\Database\Test;

use PHPUnit\Framework\TestCase;
use Phrodo\Database\FetchedResult;
use Phrodo\Database\Query;

class QueryTest extends TestCase
{
    /**
     * Factory
     *
     * @var Factory
     */
    protected $create;

    /**
     * Setup factory
     */
    protected function setup()
    {
        $this->create = new Factory($this);
    }

    /**
     * Minify SQL query by removing unused whitespace
     *
     * @param string $query
     * @return string
     */
    protected function minifySQL($query)
    {
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
     * @param string $expected
     * @param string $actual
     */
    protected function assertSQL($expected, $actual)
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
     * @return callable
     */
    protected function sql($expected)
    {
        return $this->callback(function ($query) use ($expected) {
            return $this->minifySQL($query) == $this->minifySQL($expected);
        });
    }

    /**
     * Test select expressions
     */
    public function testSelectExpressions()
    {
        $query = $this->create->query();
        $this->assertEquals(
            '*',
            $query->select()->getSelect()
        );

        $query = $this->create->query();
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

        $query = $this->create->query();
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
            'users',
            $this->create->query()
                ->from('users')
                ->getFrom()
        );
        $this->assertEquals(
            'users u',
            $this->create->query()
                ->from('users', 'u')
                ->getFrom()
        );
        $this->assertEquals(
            'users,groups',
            $this->create->query()
                ->from(['users', 'groups'])
                ->getFrom()
        );
        $this->assertEquals(
            'users u,groups g',
            $this->create->query()
                ->from(['u' => 'users', 'g' => 'groups'])
                ->getFrom()
        );
    }

    /**
     * Test into
     */
    public function testInto()
    {
        $this->assertEquals(
            'users',
            $this->create->query()
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
            'users u INNER JOIN teams t ON u.team_id = t.id',
            $this->create->query()
                ->from('users', 'u')
                ->innerJoin('teams', 't', 'u.team_id = t.id')
                ->getFrom()
        );
        $this->assertSQL(
            'users u RIGHT JOIN teams t ON u.team_id = t.id',
            $this->create->query()
                ->from('users', 'u')
                ->rightJoin('teams', 't', 'u.team_id = t.id')
                ->getFrom()
        );
        $this->assertSQL(
            'users u
             LEFT JOIN users_groups ug ON u.id = ug.user_id
             LEFT JOIN groups g ON g.id = ug.group_id',
            $this->create->query()
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
            $this->create->query()
                ->where(['username' => 'john'])
                ->getWhere()
        );
        $this->assertSQL(
            "username='john' AND email='john@example.com'",
            $this->create->query()
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
            $this->create->query()->getGroupBy()
        );
        $this->assertSQL(
            'id',
            $this->create->query()->groupBy('id')->getGroupBy()
        );
    }

    /**
     * Test having
     */
    public function testHaving()
    {
        $this->assertSQL(
            "COUNT(*) = '3'",
            $this->create->query()->having(['COUNT(*)' => 3])->getHaving()
        );
        $this->assertSQL(
            "COUNT(*) > '1'",
            $this->create->query()->having('COUNT(*) > ?', 1)->getHaving()
        );
    }

    /**
     * Test order by
     */
    public function testOrderBy()
    {
        $this->assertSQL(
            '',
            $this->create->query()->getOrderBy()
        );
        $this->assertSQL(
            'date ASC',
            $this->create->query()->orderBy('date ASC')->getOrderBy()
        );
    }

    /**
     * Test limit
     */
    public function testLimit()
    {
        $this->assertSame(
            '',
            $this->create->query()->getLimit()
        );
        $this->assertSame(
            '10',
            $this->create->query()->limit(10)->getLimit()
        );
    }

    /**
     * Test offset
     */
    public function testOffset()
    {
        $this->assertSame(
            '',
            $this->create->query()->offset(20)->getLimit()
        );
        $this->assertSame(
            '20,10',
            $this->create->query()->limit(10)->offset(20)->getLimit()
        );
    }

    /**
     * Test select
     */
    public function testSelectBuilder()
    {
        $connection = $this->create->mockedConnection(null, ['query']);
        $select     = $connection->select();

        $this->assertInstanceOf('Phrodo\Database\Query', $select);
        $this->assertSQL(
            "SELECT 1 FROM dual",
            $connection->select(1)->from('dual')->getQuery()
        );
        $this->assertSQL(
            "SELECT g.*, COUNT(1) as active_users
             FROM users u
             LEFT JOIN users_groups ug ON u.id = ug.user_id
             LEFT JOIN groups g ON g.id = ug.group_id
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

        $result = new FetchedResult([['id' => 1, 'username' => 'john']]);
        $connection
            ->expects($this->once())
            ->method('query')
            ->with($this->sql("SELECT * FROM users WHERE id = '1'"))
            ->willReturn($result);

        $this->assertSame(
            $result,
            $connection->select()->from('users')->where('id = ?', 1)->query()
        );
    }

    /**
     * Test insert
     */
    public function testInsertBuilder()
    {
        $connection = $this->create->mockedConnection(null, ['execute']);
        $insert     = $connection->insert('users');

        $this->assertInstanceOf('Phrodo\Database\Query', $insert);
        $this->assertSame('users', $insert->getTable());

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->sql("INSERT INTO users (`username`) VALUES ('sam')"))
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
        $connection = $this->create->mockedConnection(null, ['execute']);
        $update     = $connection->update('users');

        $this->assertInstanceOf('Phrodo\Database\Query', $update);
        $this->assertSame('users', $update->getTable());
        $this->assertSQL(
            "UPDATE users
             SET `active` = '0'",
            $update->set(['active' => 0])->getQuery()
        );
        $this->assertSQL(
            "UPDATE users
             SET `active` = '0'
             WHERE email LIKE '%@example.com'",
            $update->where('email LIKE ?', '%@example.com')->getQuery()
        );
        $this->assertSQL(
            "UPDATE users
             SET `active` = '0'
             WHERE email LIKE '%@example.com'
             ORDER BY id
             LIMIT 10",
            $update->orderBy('id')->limit(10)->getQuery()
        );

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->sql("UPDATE users SET `username`='sam' WHERE `id`='2'"))
            ->willReturn(1);

        $this->assertEquals(
            1,
            $connection->update('users', ['username' => 'sam'], ['id' => 2])
        );
    }

    /**
     * Test delete
     */
    public function testDeleteBuilder()
    {
        $connection = $this->create->mockedConnection(null, ['execute']);
        $delete     = $connection->delete('users');

        $this->assertInstanceOf('Phrodo\Database\Query', $delete);
        $this->assertSame('users', $delete->getTable());
        $this->assertSQL(
            'DELETE FROM users',
            $delete->getQuery()
        );
        $this->assertSQL(
            "DELETE FROM users WHERE id='3'",
            $delete->where('id = ?', 3)->getQuery()
        );
        $this->assertSQL(
            "DELETE FROM users WHERE id='3' LIMIT 1",
            $delete->limit(1)->getQuery()
        );

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->sql("DELETE FROM users WHERE `id`='3'"))
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
        $connection = $this->create->connection();

        $this->assertSQL(
            "SELECT 1 FROM dual",
            $connection
                ->select(1)
                ->from('dual')
                ->getQuery()
        );
        $this->assertSQL(
            "SELECT 1 FROM dual",
            (string) $connection
                ->select(1)
                ->from('dual')
        );

        $this->expectException('RuntimeException');
        $query = new Query($connection);
        $query->getQuery();
    }
}
