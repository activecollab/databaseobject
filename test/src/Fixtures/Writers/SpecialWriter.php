<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\PoolInterface;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
class SpecialWriter extends Writer
{
    /**
     * @var bool
     */
    private $is_special;

    /**
     * @param ConnectionInterface  $connection
     * @param PoolInterface        $pool
     * @param LoggerInterface|null $log
     * @param bool                 $is_special
     */
    public function __construct(ConnectionInterface &$connection, PoolInterface &$pool, LoggerInterface &$log = null, $is_special = false)
    {
        parent::__construct($connection, $pool, $log);

        $this->is_special = $is_special;
    }

    /**
     * @return bool
     */
    public function isSpecial()
    {
        return $this->is_special;
    }
}