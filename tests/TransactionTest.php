<?php

namespace Neat\Database\Test;

use Neat\Database\Connection;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TransactionTest extends TestCase
{
    use Factory;

    /**
     * Test transaction
     */
    public function testTransaction()
    {
        $pdo = $this->mockedPdo(['beginTransaction', 'commit', 'rollback']);
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

        $connection = $this->connection($pdo);

        $this->assertInstanceOf(Connection::class, $connection);
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
        $pdo = $this->mockedPdo(['beginTransaction', 'exec', 'commit', 'rollback']);
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

        $connection = $this->connection($pdo);
        $connection->transaction(function () use ($connection) {
            $connection->execute('DELETE FROM users WHERE id = 1');
        });
    }

    /**
     * Test rollback
     */
    public function testRollback()
    {
        $pdo = $this->mockedPdo(['beginTransaction', 'commit', 'rollback']);
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('rollback')
            ->willReturn(true);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Whoops');

        $connection = $this->connection($pdo);
        $connection->transaction(function () use ($connection) {
            throw new RuntimeException('Whoops');
        });
    }

    /**
     * Test nesting transactions
     */
    public function testNestingTransactions()
    {
        $pdo = $this->mockedPdo(['beginTransaction', 'commit', 'rollback']);
        $pdo->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessageRegExp('|cannot.+start|i');

        $transaction = $this->connection($pdo);
        $transaction->start();
        $transaction->start();
    }

    /**
     * Test commit without start
     */
    public function testCommitWithoutStart()
    {
        $pdo = $this->mockedPdo(['beginTransaction', 'commit', 'rollback']);
        $pdo->expects($this->never())
            ->method($this->anything());

        $this->expectException('RuntimeException');
        $this->expectExceptionMessageRegExp('|cannot.+commit|i');

        $transaction = $this->connection($pdo);
        $transaction->commit();
    }

    /**
     * Test rollback without start
     */
    public function testRollbackWithoutStart()
    {
        $pdo = $this->mockedPdo(['beginTransaction', 'commit', 'rollback']);
        $pdo->expects($this->never())
            ->method($this->anything());

        $this->expectException('RuntimeException');
        $this->expectExceptionMessageRegExp('|cannot.+rollback|i');

        $transaction = $this->connection($pdo);
        $transaction->rollback();
    }

    /**
     * Test start failure
     */
    public function testStartFailure()
    {
        $pdo = $this->mockedPdo(['beginTransaction', 'commit', 'rollback']);
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(false);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessageRegExp('|fail.+start|i');

        $connection = $this->connection($pdo);
        $connection->transaction(function () {
        });
    }

    /**
     * Test commit failure
     */
    public function testCommitFailure()
    {
        $pdo = $this->mockedPdo(['beginTransaction', 'commit', 'rollback']);
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('commit')
            ->willReturn(false);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessageRegExp('|fail.+commit|i');

        $connection = $this->connection($pdo);
        $connection->transaction(function () {
        });
    }

    /**
     * Test rollback failure
     */
    public function testRollbackFailure()
    {
        $pdo = $this->mockedPdo(['beginTransaction', 'commit', 'rollback']);
        $pdo->expects($this->at(0))
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo->expects($this->at(1))
            ->method('rollback')
            ->willReturn(false);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessageRegExp('|fail.+rollback|i');

        $connection = $this->connection($pdo);
        $connection->transaction(function () {
            throw new RuntimeException('Whoops');
        });
    }
}
