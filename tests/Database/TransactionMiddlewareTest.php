<?php
namespace League\Tactician\Cake\Database;

use Cake\Database\Connection;
use Mockery\MockInterface;

class TransactionMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection|MockInterface
     */
    private $connection;

    /**
     * @var TransactionMiddleware
     */
    private $middleware;

    /**
     * Set up the Connection Mock and Middleware
     */
    protected function setUp()
    {
        $this->connection = \Mockery::mock(Connection::class);
        $this->middleware = new TransactionMiddleware($this->connection);
    }

    public function testCommandSucceedsAndTransactionIsCommitted()
    {
        $this->connection->shouldReceive('begin')->once();
        $this->connection->shouldReceive('commit')->once()->andReturn(true);
        $this->connection->shouldReceive('rollback')->never();

        $timesExecuted = 0;
        $next = function () use (&$timesExecuted) {
            $timesExecuted++;
        };

        $this->middleware->execute(new \stdClass(), $next);

        $this->assertEquals(1, $timesExecuted);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Command failed
     */
    public function testCommandFailsAndTransactionIsRolledBack()
    {
        $this->connection->shouldReceive('begin')->once();
        $this->connection->shouldReceive('commit')->never();
        $this->connection->shouldReceive('rollback')->once();

        $next = function () {
            throw new \Exception('Command failed');
        };

        $this->middleware->execute(new \stdClass(), $next);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Failed to commit the transaction.
     */
    public function testCommandSucceedsButTransactionFailsReturningFalse()
    {
        $this->connection->shouldReceive('begin')->once();
        $this->connection->shouldReceive('commit')->once()->andReturn(false);
        $this->connection->shouldReceive('rollback')->once();

        $next = function () {
            // no-op
        };

        $this->middleware->execute(new \stdClass(), $next);
    }
}
