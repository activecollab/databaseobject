<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface PoolInterface
{
    /**
     * Produce new instance of $type.
     *
     * @param  string          $type
     * @param  array|null      $attributes
     * @param  bool            $save
     * @return EntityInterface
     */
    public function &produce($type, array $attributes = null, $save = true);

    /**
     * Update an instance.
     *
     * @param  EntityInterface $instance
     * @param  array|null      $attributes
     * @param  bool            $save
     * @return EntityInterface
     */
    public function &modify(EntityInterface &$instance, array $attributes = null, $save = true);

    /**
     * Scrap an instance (move it to trash, if object supports, or delete it).
     *
     * @param  EntityInterface $instance
     * @param  bool            $force_delete
     * @return EntityInterface
     */
    public function &scrap(EntityInterface &$instance, $force_delete = false);

    /**
     * Register producer instance for the given type.
     *
     * @param string            $type
     * @param ProducerInterface $producer
     */
    public function registerProducer($type, ProducerInterface $producer);

    /**
     * Register producerby providing a producer class name.
     *
     * @param string $type
     * @param string $producer_class
     */
    public function registerProducerByClass($type, $producer_class);

    /**
     * Return object from object pool by the given type and ID; if object is not found, return NULL.
     *
     * @param  string      $type
     * @param  int         $id
     * @param  bool        $use_cache
     * @return object|null
     */
    public function &getById($type, $id, $use_cache = true);

    /**
     * Return object from object pool by the given type and ID; if object is not found, raise an exception.
     *
     * @param  string $type
     * @param  int    $id
     * @param  bool   $use_cache
     * @return object
     */
    public function &mustGetById($type, $id, $use_cache = true);

    /**
     * Reload an object of the give type with the given ID.
     *
     * @param  string $type
     * @param  int    $id
     * @return object
     */
    public function &reload($type, $id);

    /**
     * Check if object #ID of $type is in the pool.
     *
     * @param  string $type
     * @param  int    $id
     * @return bool
     */
    public function isInPool($type, $id);

    /**
     * Add object to the pool.
     *
     * @param EntityInterface $object
     */
    public function remember(EntityInterface &$object);

    /**
     * Forget object if it is loaded in memory.
     *
     * @param string    $type
     * @param array|int $id
     */
    public function forget($type, $id);

    /**
     * Return number of records of the given type that match the given conditions.
     *
     * @param  string            $type
     * @param  array|string|null $conditions
     * @return int
     */
    public function count($type, $conditions = null);

    /**
     * Return true if object of the given type with the given ID exists.
     *
     * @param  string $type
     * @param  int    $id
     * @return bool
     */
    public function exists($type, $id);

    /**
     * Find records by type.
     *
     * @param  string $type
     * @return Finder
     */
    public function find($type);

    /**
     * Return result by a prepared SQL statement.
     *
     * @param  string                                 $type
     * @param  string                                 $sql
     * @param  mixed                                  $arguments
     * @return ResultInterface|EntityInterface[]|null
     */
    public function findBySql($type, $sql, ...$arguments);

    /**
     * Return table name by type.
     *
     * @param  string $type
     * @param  bool   $escaped
     * @return string
     */
    public function getTypeTable($type, $escaped = false);

    /**
     * Return a list of fields that are managed by the type.
     *
     * @param  string $type
     * @return array
     */
    public function getTypeFields($type);

    /**
     * Return a list of generated type fields that $type is aware of.
     *
     * @param  string $type
     * @return array
     */
    public function getGeneratedTypeFields($type);

    /**
     * Get a particular type property, and make it (using $callback) if it is not set already.
     *
     * @param  string   $type
     * @param  string   $property
     * @param  callable $callback
     * @return mixed
     */
    public function getTypeProperty($type, $property, callable $callback);

    /**
     * Return a list of escaped field names for the given type.
     *
     * @param  string $type
     * @return string
     */
    public function getEscapedTypeFields($type);

    /**
     * Return default order by for the given type.
     *
     * @param  string   $type
     * @return string[]
     */
    public function getTypeOrderBy($type);

    /**
     * Return escaped list of fields that we can order by.
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
     * Return registered type for the given $type. This function is subclassing aware.
     *
     * @param  string      $type
     * @return string|null
     */
    public function getRegisteredType($type);

    /**
     * Get registered type's class, or throw an execption if type is not regiestered.
     *
     * @param  string $type
     * @return string
     */
    public function requireRegisteredType($type);

    /**
     * Return true if $type is registered.
     *
     * @param  string $type
     * @return bool
     */
    public function isTypeRegistered($type);

    /**
     * Return true if $type is polymorph (has type column that is used to figure out a class of individual record).
     *
     * @param  string $type
     * @return bool
     */
    public function isTypePolymorph($type);

    /**
     * @param string[] $types
     */
    public function registerType(...$types);

    /**
     * Return trait names by object.
     *
     * @param  string $type
     * @return array
     */
    public function getTraitNamesByType($type);

    /**
     * @return string
     */
    public function getDefaultFinderClass();

    /**
     * @return string
     */
    public function getDefaultProducerClass();
}
