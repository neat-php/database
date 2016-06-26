<?php namespace Phrodo\Database\Test;

class TransactionTest extends \PHPUnit_Framework_TestCase
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
     * Test transaction
     */
    public function testTransaction()
    {
        $pdo = $this->create->mockedPdo();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('commit')
            ->willReturn(true);
        $pdo->expects($this->at(2))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(3))
            ->method('rollBack')
            ->willReturn(true);

        $connection = $this->create->connection($pdo);

        $this->assertInstanceOf('Phrodo\Database\Connection', $connection);
        $connection->start();
        $connection->commit();
        $connection->start();
        $connection->rollback();
    }

    /**
     * Test commit
     */
    public function testCommit()
    {
        $pdo = $this->create->mockedPdo();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('exec')
            ->with('DELETE FROM users WHERE id = 1')
            ->willReturn(1);
        $pdo->expects($this->at(2))
            ->method('commit')
            ->willReturn(true);

        $connection = $this->create->connection($pdo);
        $connection->transaction(function () use ($connection) {
            $connection->execute('DELETE FROM users WHERE id = 1');
        });
    }

    /**
     * Test rollback
     */
    public function testRollback()
    {
        $pdo = $this->create->mockedPdo();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('rollback')
            ->willReturn(true);

        $this->setExpectedException('RuntimeException', 'Whoops');

        $connection = $this->create->connection($pdo);
        $connection->transaction(function () use ($connection) {
            throw new \RuntimeException('Whoops');
        });
    }

    /**
     * Test nesting transactions
     */
    public function testNestingTransactions()
    {
        $pdo = $this->create->mockedPdo();
        $pdo->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->setExpectedExceptionRegExp('RuntimeException', '|cannot.+start|i');

        $transaction = $this->create->connection($pdo);
        $transaction->start();
        $transaction->start();
    }

    /**
     * Test commit without start
     */
    public function testCommitWithoutStart()
    {
        $pdo = $this->create->mockedPdo();
        $pdo->expects($this->never())
            ->method($this->anything());

        $this->setExpectedExceptionRegExp('RuntimeException', '|cannot.+commit|i');

        $transaction = $this->create->connection($pdo);
        $transaction->commit();
    }

    /**
     * Test rollback without start
     */
    public function testRollbackWithoutStart()
    {
        $pdo = $this->create->mockedPdo();
        $pdo->expects($this->never())
            ->method($this->anything());

        $this->setExpectedExceptionRegExp('RuntimeException', '|cannot.+rollback|i');

        $transaction = $this->create->connection($pdo);
        $transaction->rollback();
    }

    /**
     * Test start failure
     */
    public function testStartFailure()
    {
        $pdo = $this->create->mockedPdo();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(false);

        $this->setExpectedExceptionRegExp('RuntimeException', '|fail.+start|i');

        $connection = $this->create->connection($pdo);
        $connection->transaction(function () {});
    }

    /**
     * Test commit failure
     */
    public function testCommitFailure()
    {
        $pdo = $this->create->mockedPdo();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('commit')
            ->willReturn(false);

        $this->setExpectedExceptionRegExp('RuntimeException', '|fail.+commit|i');

        $connection = $this->create->connection($pdo);
        $connection->transaction(function () {});
    }

    /**
     * Test rollback failure
     */
    public function testRollbackFailure()
    {
        $pdo = $this->create->mockedPdo();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('rollback')
            ->willReturn(false);

        $this->setExpectedExceptionRegExp('RuntimeException', '|fail.+rollback|i');

        $connection = $this->create->connection($pdo);
        $connection->transaction(function () {
            throw new \RuntimeException('Whoops');
        });
    }

}
