<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\CustomFinder;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\Finder;
use ActiveCollab\DatabaseObject\PoolInterface;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\CustomFinder
 */
class CustomFinderFinder extends Finder
{
    private $dependency;

    /**
     * @param PoolInterface        $pool
     * @param ConnectionInterface  $connection
     * @param LoggerInterface|null $log
     * @param string               $type
     * @param mixed                $dependency
     */
    public function __construct(ConnectionInterface $connection, PoolInterface $pool, LoggerInterface &$log = null, $type, $dependency)
    {
        parent::__construct($connection, $pool, $log, $type);

        $this->dependency = $dependency;
    }

    /**
     * @return mixed
     */
    public function getDependency()
    {
        return $this->dependency;
    }
}
