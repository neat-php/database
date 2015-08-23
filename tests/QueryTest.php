<?php namespace Phrodo\Database\Test;

use Phrodo\Database\Connection;
use Phrodo\Database\Query;
use PDO;

class QueryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Create PDO
     *
     * @return PDO
     */
    private function createPDO()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * Get mocked connection
     *
     * @return Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createConnection()
    {
        $connection = $this->getMock(Connection::class, ['quote', 'query', 'execute'], [$this->createPDO()]);
        $connection
            ->expects($this->any())
            ->method('quote')
            ->willReturnMap([
                ['bilbo', "'bilbo'"],
                ['pippin', "'pippin'"],
                [1, "'1'"],
                [2, "'2'"],
            ]);

        return $connection;
    }

    /**
     * Get query builder
     *
     * @param $connection
     * @return Query
     */
    protected function createQuery($connection)
    {
        if (!$connection) {
            $connection = $this->createConnection();
        }

        return new Query($connection);
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
        $connection = $this->createConnection();

        $select = $connection->select();
        $this->assertInstanceOf('Phrodo\Database\Query', $select);
    }

    /**
     * Test insert
     */
    public function testInsert()
    {
        $connection = $this->createConnection();
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
        $connection = $this->createConnection();
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
        $connection = $this->createConnection();
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
