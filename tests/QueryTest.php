<?php namespace Phrodo\Database\Test;

class QueryTest extends \PHPUnit_Framework_TestCase
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
     * Assert SQL matches expectation
     *
     * @param string $expected
     * @param string $actual
     */
    protected function assertSQL($expected, $actual)
    {
        $filter = function ($sql) {
            return preg_replace('|\s+|m', '', $sql);
        };

        $this->assertEquals($filter($expected), $filter($actual));
    }

    public function testSelect()
    {
        $query = $this->create->query();
        $this->assertEquals(
            '*',
            $query->select()->getSelect()
        );
        $this->assertEquals(
            'id',
            $query->select('id')->getSelect()
        );
        $this->assertEquals(
            'id,username',
            $query->select(['id', 'username'])->getSelect()
        );
        $this->assertEquals(
            'COUNT(*) AS amount',
            $query->select(['amount' => 'COUNT(*)'])->getSelect()
        );
        $this->assertEquals(
            'id,MIN(price) AS min_price',
            $query->select(['id', 'min_price' => 'MIN(price)'])->getSelect()
        );
    }

    public function testFrom()
    {
        $this->assertEquals(
            'users',
            $this->create->query()->from('users')->getFrom()
        );
        $this->assertEquals(
            'users u',
            $this->create->query()->from('users', 'u')->getFrom()
        );
        $this->assertEquals(
            'users,groups',
            $this->create->query()->from(['users', 'groups'])->getFrom()
        );
        $this->assertEquals(
            'users u,groups g',
            $this->create->query()->from(['u' => 'users', 'g' => 'groups'])->getFrom()
        );
    }

    /**
     * Test select
     */
    public function testSelectBuilder()
    {
        $connection = $this->create->connection();
        $select     = $connection->select();

        $this->assertInstanceOf('Some\Database\Query\Select', $select);
    }

    /**
     * Test insert
     */
    public function testInsertBuilder()
    {
        $connection = $this->create->mockedConnection(null, ['execute']);
        $insert     = $connection->insert('users');

        $this->assertInstanceOf('Some\Database\Query\Insert', $insert);
        $this->assertSame('users', $insert->getTable());

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($query) {
                return preg_replace('/\s+/', ' ', $query) == "INSERT INTO users (username) VALUES ('bilbo')";
            }))
            ->willReturn(1);

        $this->assertEquals(1, $connection->insert('users', ['username' => 'bilbo']));
    }

    /**
     * Test update
     */
    public function testUpdateBuilder()
    {
        $connection = $this->create->mockedConnection(null, ['execute']);
        $query      = $connection->update('users');

        $this->assertInstanceOf('Some\Database\Query\Update', $query);
        $this->assertSame('users', $query->getTable());

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($query) {
                return preg_replace('/\s+/', ' ', $query) == "UPDATE users SET username='pippin' WHERE id='2'";
            }))
            ->willReturn(1);

        $this->assertEquals(1,
            $connection->update('users', ['username' => 'pippin'],
                ['id' => 2]));
    }

    /**
     * Test delete
     */
    public function testDeleteBuilder()
    {
        $connection = $this->create->mockedConnection(null, ['execute']);
        $query      = $connection->delete('users');

        $this->assertInstanceOf('Some\Database\Query\Delete', $query);
        $this->assertSame('users', $query->getTable());

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($query) {
                return preg_replace('/\s+/', ' ', $query) == "DELETE FROM users WHERE id='1'";
            }))
            ->willReturn(1);

        $this->assertEquals(1, $connection->delete('users', ['id' => 1]));
    }

}
