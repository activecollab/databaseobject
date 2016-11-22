<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\ContainerAccess\ContainerAccessInterface;
use ActiveCollab\ContainerAccess\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Producer implements ProducerInterface, ContainerAccessInterface
{
    use ContainerAccessInterfaceImplementation;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @param ConnectionInterface  $connection
     * @param PoolInterface        $pool
     * @param LoggerInterface|null $log
     */
    public function __construct(ConnectionInterface &$connection, PoolInterface &$pool, LoggerInterface &$log = null)
    {
        $this->connection = $connection;
        $this->pool = $pool;
        $this->log = $log;
    }

    /**
     * Produce new instance of $type.
     *
     * @param  string          $type
     * @param  array|null      $attributes
     * @param  bool            $save
     * @return EntityInterface
     */
    public function &produce($type, array $attributes = null, $save = true)
    {
        /** @var object|EntityInterface $object */
        $object = new $type($this->connection, $this->pool, $this->log);

        if ($object instanceof ContainerAccessInterface && $this->hasContainer()) {
            $object->setContainer($this->getContainer());
        }

        if ($attributes) {
            foreach ($attributes as $k => $v) {
                if ($object->fieldExists($k)) {
                    $object->setFieldValue($k, $v);
                } else {
                    $object->setAttribute($k, $v);
                }
            }
        }

        if ($save) {
            $object->save();
        }

        return $object;
    }

    /**
     * Update an instance.
     *
     * @param  EntityInterface $instance
     * @param  array|null      $attributes
     * @param  bool            $save
     * @return EntityInterface
     */
    public function &modify(EntityInterface &$instance, array $attributes = null, $save = true)
    {
        if ($attributes) {
            foreach ($attributes as $k => $v) {
                if ($instance->fieldExists($k)) {
                    $instance->setFieldValue($k, $v);
                } else {
                    $instance->setAttribute($k, $v);
                }
            }
        }

        if ($save) {
            $instance->save();
        }

        return $instance;
    }

    /**
     * Scrap an instance (move it to trash, if object can be trashed, or delete it).
     *
     * @param  EntityInterface $instance
     * @param  bool            $force_delete
     * @return EntityInterface
     */
    public function &scrap(EntityInterface &$instance, $force_delete = false)
    {
        if (!$force_delete && $instance instanceof ScrapInterface) {
            return $instance->scrap();
        } else {
            return $instance->delete();
        }
    }
}
