<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Entity;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\PoolInterface;
use Psr\Log\LoggerInterface;

abstract class Manager implements ManagerInterface
{
    protected $connection;
    protected $pool;
    protected $logger;

    public function __construct(ConnectionInterface $connection, PoolInterface $pool, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->pool = $pool;
        $this->logger = $logger;
    }
}
