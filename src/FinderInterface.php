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

interface FinderInterface
{
    public function getType(): string;
    public function getSelectSql(): string;
    public function getSelectIdsSql(): string;
    public function __toString(): string;

    // ---------------------------------------------------
    //  Configuration
    // ---------------------------------------------------

    public function where(string $pattern, ...$arguments): FinderInterface;
    public function orderBy(string $order_by): FinderInterface;
    public function limit(int $offset, int $limit): FinderInterface;
    public function join(string $type, string $field_name = null): FinderInterface;
    public function joinTable(string $table_name, string $field_name = null): FinderInterface;

    // ---------------------------------------------------
    //  Execution
    // ---------------------------------------------------

    /**
     * Return number of records that match the given criteria.
     *
     * @return int
     */
    public function count(): int;

    public function exists(): bool;
    public function existsOne(): bool;

    /**
     * Return all records that match the given criteria.
     *
     * @return ResultInterface|EntityInterface[]|iterable|null
     */
    public function all(): ?iterable;

    /**
     * Return first record that matches the given criteria.
     *
     * @return EntityInterface|null
     */
    public function first(): ?EntityInterface;

    /**
     * Return array of ID-s that match the given criteria.
     *
     * @return int[]|iterable|null
     */
    public function ids(): ?iterable;

    /**
     * Prepare SQL and load one or more records.
     *
     * @return mixed
     */
    public function execute();
}
