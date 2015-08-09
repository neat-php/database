<?php namespace Phrodo\Database\Test;

trait Setup
{

    /**
     * Connection instance
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Setup PDO and connection
     */
    public function setup()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE users (
                        id INTEGER PRIMARY KEY,
                        username TEXT
                    )');
        $pdo->exec("INSERT INTO users (id, username) VALUES
                    (1, 'john'),
                    (2, 'jane'),
                    (3, 'bob')");

        $this->connection = new \Phrodo\Database\Connection($pdo);
    }

}
