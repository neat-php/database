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

    /**
     * Test select
     */
    public function testSelectBuilder()
    {
        $connection = $this->create->connection();

        $select = $connection->select();
        $this->assertInstanceOf('Phrodo\Database\Query', $select);
    }

    /**
     * Test insert
     */
    public function testInsert()
    {
        $connection = $this->create->mockedConnection(null, ['execute']);
        $query      = $connection->insert('users');

        $this->assertInstanceOf('Phrodo\Database\Query', $query);
        $this->assertSame('users', $query->getTable());

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($query) {
                return preg_replace('/\s+/', ' ',
                    $query) == "INSERT INTO users (username) VALUES ('bilbo')";
            }))
            ->willReturn(1);

        $this->assertEquals(1,
            $connection->insert('users', ['username' => 'bilbo']));
    }

    /**
     * Test update
     */
    public function testUpdate()
    {
        $connection = $this->create->mockedConnection(null, ['execute']);
        $query      = $connection->update('users');

        $this->assertInstanceOf('Phrodo\Database\Query', $query);
        $this->assertSame('users', $query->getTable());

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($query) {
                return preg_replace('/\s+/', ' ',
                    $query) == "UPDATE users SET username='pippin' WHERE id='2'";
            }))
            ->willReturn(1);

        $this->assertEquals(1,
            $connection->update('users', ['username' => 'pippin'],
                ['id' => 2]));
    }

    /**
     * Test delete
     */
    public function testDelete()
    {
        $connection = $this->create->mockedConnection(null, ['execute']);
        $query      = $connection->delete('users');

        $this->assertInstanceOf('Phrodo\Database\Query', $query);
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
