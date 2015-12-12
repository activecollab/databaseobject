<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\ConnectionInterface;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Producer implements ProducerInterface
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * @param ConnectionInterface $connection
     * @param PoolInterface       $pool
     */
    public function __construct(ConnectionInterface &$connection, PoolInterface &$pool)
    {
        $this->connection = $connection;
        $this->pool = $pool;
    }

    /**
     * Produce new instance of $type
     *
     * @param  string          $type
     * @param  array|null      $attributes
     * @param  boolean         $save
     * @return ObjectInterface
     */
    public function &produce($type, array $attributes = null, $save = true)
    {
        /** @var ObjectInterface $object */
        $object = new $type($this->pool, $this->connection);

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
     * Update an instance
     *
     * @param  ObjectInterface $instance
     * @param  array|null      $attributes
     * @param  boolean         $save
     * @return ObjectInterface
     */
    public function &modify(ObjectInterface &$instance, array $attributes = null, $save = true)
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
     * Scrap an instance (move it to trash, if object can be trashed, or delete it)
     *
     * @param  ObjectInterface $instance
     * @param  boolean         $force_delete
     * @return ObjectInterface
     */
    public function &scrap(ObjectInterface &$instance, $force_delete = false)
    {
        if (!$force_delete && $instance instanceof ScrapInterface) {
            return $instance->scrap();
        } else {
            return $instance->delete();
        }
    }
}
