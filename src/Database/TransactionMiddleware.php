<?php
namespace League\Tactician\Cake\Database;

use Cake\Database\Connection;
use League\Tactician\Middleware;

class TransactionMiddleware implements Middleware
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param object $command
     * @param callable $next
     *
     * @return mixed
     * @throws \Exception when Transaction fails to commit.
     */
    public function execute($command, callable $next)
    {
        $this->connection->begin();

        try {
            $returnValue = $next($command);
            $isCommitted = $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }

        if (!$isCommitted) {
            throw new \Exception('Failed to commit the transaction.');
        }

        return $returnValue;
    }
}
