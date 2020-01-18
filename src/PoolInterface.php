<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;

interface PoolInterface
{
    public function produce($type, array $attributes = null, $save = true): EntityInterface;
    public function modify(EntityInterface &$instance, array $attributes = null, $save = true): EntityInterface;
    public function scrap(EntityInterface &$instance, $force_delete = false): EntityInterface;

    public function getDefaultProducerClass(): string;
    public function getDefaultProducer(): ProducerInterface;
    public function setDefaultProducer(ProducerInterface $producer): PoolInterface;
    public function setDefaultProducerClass(string $default_producer_class): PoolInterface;
    public function registerProducer(string $type, ProducerInterface $producer): PoolInterface;
    public function registerProducerByClass(string $type, string $producer_class): PoolInterface;

    public function getById(string $type, int $id, bool $use_cache = true): ?EntityInterface;
    public function mustGetById(string $type, int $id, bool $use_cache = true): EntityInterface;
    public function reload(string $type, int $id): ?EntityInterface;

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
    public function remember(EntityInterface $object): void;

    /**
     * Forget object if it is loaded in memory.
     *
     * @param string    $type
     * @param array|int $id
     */
    public function forget(string $type, int $id): void;

    /**
     * Return number of records of the given type that match the given conditions.
     *
     * @param  string            $type
     * @param  array|string|null $conditions
     * @return int
     */
    public function count(string $type, $conditions = null): int;

    /**
     * Return true if object of the given type with the given ID exists.
     *
     * @param  string $type
     * @param  int    $id
     * @return bool
     */
    public function exists(string $type, int $id): bool;

    /**
     * Find records by type.
     *
     * @param  string $type
     * @return Finder
     */
    public function find(string $type): FinderInterface;

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
     * Return interface that is used to detect if type is polymorph.
     *
     * @return string|null
     */
    public function getPolymorphTypeInterface();

    /**
     * Set interface that is used to detect if type is polymorph.
     *
     * @param  string|null $value
     * @return $this
     */
    public function &setPolymorphTypeInterface($value);

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
}
