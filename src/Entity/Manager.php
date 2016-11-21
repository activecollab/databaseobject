<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Entity;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\PoolInterface;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseObject\Entity
 */
abstract class Manager implements ManagerInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @param PoolInterface        $pool
     * @param ConnectionInterface  $connection
     * @param LoggerInterface|null $log
     */
    public function __construct(ConnectionInterface $connection, PoolInterface $pool, LoggerInterface &$log = null)
    {
        $this->connection = $connection;
        $this->pool = $pool;
        $this->log = $log;
    }

    /**
     * @return ConnectionInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return PoolInterface
     */
    protected function getPool()
    {
        return $this->pool;
    }

    /**
     * @return LoggerInterface|null
     */
    protected function getLog()
    {
        return $this->log;
    }
}
