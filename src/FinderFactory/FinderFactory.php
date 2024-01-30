<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\FinderFactory;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\Finder;
use ActiveCollab\DatabaseObject\FinderInterface;
use ActiveCollab\DatabaseObject\PoolInterface;
use Psr\Log\LoggerInterface;

class FinderFactory implements FinderFactoryInterface
{
    public function __construct(
        private ConnectionInterface $connection,
        private PoolInterface $pool,
        private LoggerInterface $logger,
    )
    {
    }

    public function produceFinder(
        string $type,
        string $where_pattern = null,
        mixed ...$where_arguments,
    ): FinderInterface
    {
        $finder = new Finder(
            $this->connection,
            $this->pool,
            $this->logger,
            $type
        );

        if ($where_pattern) {
            $finder->where($where_pattern, ...$where_arguments);
        }

        return $finder;
    }
}
