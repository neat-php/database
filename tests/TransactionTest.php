<?php namespace Phrodo\Database\Test;

use Phrodo\Database\Connection;

class TransactionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Get mocked PDO instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\PDO
     */
    private function createMockPDO()
    {
        return $this->getMock('PDO', [], ['sqlite::memory:']);
    }

    /**
     * Get a PDO instance
     *
     * @param object $pdo
     * @return Connection
     */
    private function createConnection($pdo = null)
    {
        return new Connection($pdo ?: $this->createMockPDO());
    }

    public function testTransaction()
    {
        $pdo = $this->createMockPDO();
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

        $connection  = $this->createConnection($pdo);
        $transaction = $connection->transaction();

        $this->assertInstanceOf('Phrodo\Database\Transaction', $transaction);
        $transaction->start();
        $transaction->commit();
        $transaction->start();
        $transaction->rollback();
    }

    public function testTransactionCommit()
    {
        $pdo = $this->createMockPDO();
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

        $connection = $this->createConnection($pdo);
        $connection->transaction(function () use ($connection) {
            $connection->execute('DELETE FROM users WHERE id = 1');
        });
    }

    public function testTransactionRollback()
    {
        $pdo = $this->createMockPDO();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('rollback')
            ->willReturn(true);

        $this->setExpectedException('RuntimeException', 'Whoops');

        $connection = $this->createConnection($pdo);
        $connection->transaction(function () use ($connection) {
            throw new \RuntimeException('Whoops');
        });
    }

    public function testTransactionNesting()
    {
        $pdo = $this->createMockPDO();
        $pdo->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->setExpectedExceptionRegExp('RuntimeException', '|cannot.+start|i');

        $connection  = $this->createConnection($pdo);
        $transaction = $connection->transaction();
        $transaction->start();
        $transaction->start();
    }

    public function testTransactionCommitWithoutStart()
    {
        $pdo = $this->createMockPDO();
        $pdo->expects($this->never())
            ->method($this->anything());

        $this->setExpectedExceptionRegExp('RuntimeException', '|cannot.+commit|i');

        $connection  = $this->createConnection($pdo);
        $transaction = $connection->transaction();
        $transaction->commit();
    }

    public function testTransactionRollbackWithoutStart()
    {
        $pdo = $this->createMockPDO();
        $pdo->expects($this->never())
            ->method($this->anything());

        $this->setExpectedExceptionRegExp('RuntimeException', '|cannot.+rollback|i');

        $connection  = $this->createConnection($pdo);
        $transaction = $connection->transaction();
        $transaction->rollback();
    }

    public function testTransactionStartFailure()
    {
        $pdo = $this->createMockPDO();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(false);

        $this->setExpectedExceptionRegExp('RuntimeException', '|fail.+start|i');

        $connection = $this->createConnection($pdo);
        $connection->transaction(function () {});
    }

    public function testTransactionCommitFailure()
    {
        $pdo = $this->createMockPDO();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('commit')
            ->willReturn(false);

        $this->setExpectedExceptionRegExp('RuntimeException', '|fail.+commit|i');

        $connection = $this->createConnection($pdo);
        $connection->transaction(function () {});
    }

    public function testTransactionRollbackFailure()
    {
        $pdo = $this->createMockPDO();
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('rollback')
            ->willReturn(false);

        $this->setExpectedExceptionRegExp('RuntimeException', '|fail.+rollback|i');

        $connection = $this->createConnection($pdo);
        $connection->transaction(function () {
            throw new \RuntimeException('Whoops');
        });
    }

}
