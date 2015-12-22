<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;
use Psr\Log\LoggerInterface;
use ReflectionClass;

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
        $object = new $type($this->connection, $this->pool, $this->log);

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

    /**
     * @var ReflectionClass[]
     */
    private $type_reflection_classes = [];

    /**
     * @param  string $type
     * @return ReflectionClass
     */
    private function getTypeReflectionClass($type)
    {
        if (empty($this->type_reflection_classes[$type])) {
            $this->type_reflection_classes[$type] = new ReflectionClass($type);
        }

        return $this->type_reflection_classes[$type];
    }
}
