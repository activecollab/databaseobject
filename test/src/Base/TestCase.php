<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Base;

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseObject\Pool;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use mysqli;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;

/**
 * @package ActiveCollab\DatabaseObject\Test\Base
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * @var mysqli
     */
    protected $link;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->link = new \MySQLi(
            'localhost',
            'root',
            $this->getValidMySqlPassword(),
            'activecollab_database_object_test'
        );

        if ($this->link->connect_error) {
            throw new RuntimeException('Failed to connect to database. MySQL said: ' . $this->link->connect_error);
        }

        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->connection = new MysqliConnection($this->link);
        $this->pool = new Pool($this->connection, $logger);

        $this->logger = new Logger('DatabaseObject test');
        $this->logger->pushHandler(new TestHandler());
    }

    /**
     * Tear down test environment.
     */
    public function tearDown(): void
    {
        $this->connection = null;
        $this->pool = null;
        $this->link->close();

        parent::tearDown();
    }

    protected function getValidMySqlPassword(): string
    {
        return (string) getenv('DATABASE_CONNECTION_TEST_PASSWORD');
    }
}
