<?php

namespace ActiveCollab\DatabaseObject;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface PoolInterface
{
    /**
     * Produce new instance of $type
     *
     * @param  string          $type
     * @param  array|null      $attributes
     * @param  boolean         $save
     * @return ObjectInterface
     */
    public function produce($type, array $attributes = null, $save = true);

    /**
     * Register producer instance for the given type
     *
     * @param string            $type
     * @param ProducerInterface $producer
     */
    public function registerProducer($type, ProducerInterface $producer);

    /**
     * Register producerby providing a producer class name
     *
     * @param string $type
     * @param string $producer_class
     */
    public function registerProducerByClass($type, $producer_class);

    /**
     * Return object from object pool by the given type and ID; if object is not found, return NULL.
     *
     * @param  string      $type
     * @param  integer     $id
     * @param  boolean     $use_cache
     * @return Object|null
     */
    public function &getById($type, $id, $use_cache = true);

    /**
     * Return object from object pool by the given type and ID; if object is not found, raise an exception.
     *
     * @param  string  $type
     * @param  integer $id
     * @param  boolean $use_cache
     * @return Object
     */
    public function &mustGetById($type, $id, $use_cache = true);

    /**
     * Reload an object of the give type with the given ID
     *
     * @param  string  $type
     * @param  integer $id
     * @return Object
     */
    public function &reload($type, $id);

    /**
     * Check if object #ID of $type is in the pool
     *
     * @param  string  $type
     * @param  integer $id
     * @return boolean
     */
    public function isInPool($type, $id);

    /**
     * Add object to the pool
     *
     * @param ObjectInterface $object
     */
    public function remember(ObjectInterface &$object);

    /**
     * Forget object if it is loaded in memory
     *
     * @param string  $type
     * @param integer $id
     */
    public function forget($type, $id);

    /**
     * Return number of records of the given type that match the given conditions
     *
     * @param  string            $type
     * @param  array|string|null $conditions
     * @return integer
     */
    public function count($type, $conditions = null);

    /**
     * Return true if object of the given type with the given ID exists
     *
     * @param  string  $type
     * @param  integer $id
     * @return bool
     */
    public function exists($type, $id);

    /**
     * Find records by type
     *
     * @param  string $type
     * @return Finder
     */
    public function find($type);

    /**
     * Return table name by type
     *
     * @param  string  $type
     * @param  boolean $escaped
     * @return string
     */
    public function getTypeTable($type, $escaped = false);

    /**
     * @param  string $type
     * @return array
     */
    public function getTypeFields($type);

    /**
     * Get a particular type property, and make it (using $callback) if it is not set already
     *
     * @param  string   $type
     * @param  string   $property
     * @param  callable $callback
     * @return mixed
     */
    public function getTypeProperty($type, $property, callable $callback);

    /**
     * Return a list of escaped field names for the given type
     *
     * @param  string $type
     * @return string
     */
    public function getEscapedTypeFields($type);

    /**
     * Return default order by for the given type
     *
     * @param  string   $type
     * @return string[]
     */
    public function getTypeOrderBy($type);

    /**
     * Return escaped list of fields that we can order by
     *
     * @param  string $type
     * @return string
     */
    public function getEscapedTypeOrderBy($type);

    /**
     * @return array
     */
    public function getRegisteredTypes();

    /**
     * Return registered type for the given $type. This function is subclassing aware
     *
     * @param  string      $type
     * @return string|null
     */
    public function getRegisteredType($type);

    /**
     * Return true if $type is registered
     *
     * @param  string $type
     * @return bool
     */
    public function isTypeRegistered($type);

    /**
     * @param string[] $types
     */
    public function registerType(...$types);

    /**
     * Return trait names by object
     *
     * @param  string $type
     * @return array
     */
    public function getTraitNamesByType($type);
}
