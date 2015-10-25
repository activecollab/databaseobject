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
    private $connection;

    /**
     * @var PoolInterface
     */
    private $pool;

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
}
