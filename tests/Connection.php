<?php namespace Phrodo\Test\Database;

class Connection extends \PHPUnit_Framework_TestCase
{

    /**
     * PDO instance
     *
     * @var \Phrodo\Database\Connection
     */
    protected $connection;

    /**
     * Setup PDO and connection
     */
    public function setup()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE messages (
            id INTEGER PRIMARY KEY,
            title TEXT,
            message TEXT,
            time INTEGER
        )');

        $this->connection = new \Phrodo\Database\Connection($pdo);
    }

    /**
     * Test query
     */
    public function testPDO()
    {
        $pdo = new \PDO('sqlite::memory:');

        $this->assertInstanceOf('PDO', $this->connection->pdo());
        $this->assertNotSame($pdo, $this->connection->pdo());
        $this->assertSame($pdo, $this->connection->pdo($pdo));
    }

    public function testQuote()
    {
        $this->assertEquals('NULL', $this->connection->quote(null));
        $this->assertEquals("'34'", $this->connection->quote(34));
        $this->assertEquals("'''; --'", $this->connection->quote("'; --"));
        $this->assertEquals("'2020-02-15 01:23:45'", $this->connection->quote(new \DateTime('2020-02-15 01:23:45')));
    }

    public function testMerge()
    {
        $this->assertEquals('SELECT stuff', $this->connection->merge('SELECT stuff'));
        $this->assertEquals('SELECT stuff', $this->connection->merge('SELECT stuff', []));
        $this->assertEquals("SELECT stuff WHERE foo='1'", $this->connection->merge('SELECT stuff WHERE foo=?', [1]));
        $this->assertEquals("SELECT stuff WHERE foo='1'", $this->connection->merge('SELECT stuff WHERE foo=?', 1));


        //$result = $this->connection->query('SELECT * FROM messages');
        //
        //$this->assertInstanceOf('Phrodo\Database\Query', $result);
        //$this->assertEquals(1, $result->value());
    }



}
