<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\ContainerAccess\ContainerAccessInterface;
use ActiveCollab\ContainerAccess\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use Psr\Log\LoggerInterface;

class Producer implements ProducerInterface, ContainerAccessInterface
{
    use ContainerAccessInterfaceImplementation;

    public function __construct(
        protected ConnectionInterface $connection,
        protected PoolInterface $pool,
        protected LoggerInterface $logger
    )
    {
    }

    public function produce(
        string $type,
        array $attributes = null,
        bool $save = true,
    ): EntityInterface
    {
        /** @var EntityInterface $object */
        $object = new $type($this->connection, $this->pool, $this->logger);

        if ($object instanceof ContainerAccessInterface && $this->hasContainer()) {
            $object->setContainer($this->getContainer());
        }

        if ($attributes) {
            foreach ($attributes as $k => $v) {
                if ($object->entityFieldExists($k)) {
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

    public function modify(
        EntityInterface $instance,
        array $attributes = null,
        bool $save = true,
    ): EntityInterface
    {
        if ($attributes) {
            foreach ($attributes as $k => $v) {
                if ($instance->entityFieldExists($k)) {
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

    public function scrap(
        EntityInterface $instance,
        bool $force_delete = false,
    ): EntityInterface
    {
        if (!$force_delete && $instance instanceof ScrapInterface) {
            return $instance->scrap();
        }

        return $instance->delete();
    }
}
