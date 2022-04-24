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
    public function isInPool(string $type, int $id): bool;
    public function remember(EntityInterface $object): void;
    public function forget(string $type, int ...$ids_to_forget): void;

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
    public function findBySql(string $type, string $sql, ...$arguments);

    public function getTypeTable(string $type, bool $escaped = false): string;

    /**
     * Return a list of fields that are managed by the type.
     *
     * @param  string $type
     * @return array
     */
    public function getTypeFields(string $type): array;
    public function getGeneratedTypeFields(string $type): array;

    /**
     * Get a particular type property, and make it (using $callback) if it is not set already.
     */
    public function getTypeProperty(string $type, string $property, callable $callback): mixed;
    public function getEscapedTypeFields(string $type): string;
    public function getTypeFieldsReadStatement(string $type): string;
    public function getTypeOrderBy(string $type): array;
    public function getEscapedTypeOrderBy(string $type): string;
    public function getRegisteredTypes(): array;
    public function getRegisteredType(string $type): ?string;
    public function requireRegisteredType(string $type): string;
    public function isTypeRegistered(string $type): bool;

    /**
     * Return interface that is used to detect if type is polymorph.
     *
     * @return string|null
     */
    public function getPolymorphTypeInterface(): ?string;

    /**
     * Set interface that is used to detect if type is polymorph.
     *
     * @param  string|null $value
     * @return $this
     */
    public function &setPolymorphTypeInterface(?string $value): PoolInterface;

    /**
     * Return true if $type is polymorph (has type column that is used to figure out a class of individual record).
     *
     * @param  string $type
     * @return bool
     */
    public function isTypePolymorph(string $type): bool;
    public function registerType(string ...$types): PoolInterface;

    public function getTraitNamesByType(string $type): array;
    public function getDefaultFinderClass(): string;
}
