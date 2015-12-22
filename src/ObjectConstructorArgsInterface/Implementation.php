<?php

namespace ActiveCollab\DatabaseObject\ObjectConstructorArgsInterface;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\PoolInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * @property ConnectionInterface $connection
 * @property PoolInterface $pool
 * @property LoggerInterface $log
 *
 * @package ActiveCollab\DatabaseObject\ObjectConstructorArgsInterface
 */
trait Implementation
{
    /**
     * @var array
     */
    private $object_constructor_args;

    /**
     * @return array
     */
    public function getObjectConstructorArgs()
    {
        return empty($this->object_constructor_args) ? [&$this->connection, &$this->pool, &$this->log] : $this->object_constructor_args;
    }

    /**
     * @param  array $args
     * @return $this
     */
    public function &setObjectConstructorArgs(array $args)
    {
        if (empty($args)) {
            throw new InvalidArgumentException("List of constructor arguments can't be empty");
        }

        $this->object_constructor_args = $args;

        return $this;
    }
}
