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
interface FinderInterface
{
    /**
     * @return string
     */
    public function getType(): string;

    // ---------------------------------------------------
    //  Configuration
    // ---------------------------------------------------

    /**
     * Set finder conditions.
     *
     * @param  string          $pattern
     * @param  mixed           ...$arguments
     * @return FinderInterface
     */
    public function &where($pattern, ...$arguments): FinderInterface;

    /**
     * @param  string          $order_by
     * @return FinderInterface
     */
    public function &orderBy($order_by): FinderInterface;

    /**
     * @param  int             $offset
     * @param  int             $limit
     * @return FinderInterface
     */
    public function &limit($offset, $limit): FinderInterface;

    /**
     * @param  string          $type
     * @param  string          $field_name
     * @return FinderInterface
     */
    public function &join($type, $field_name = null): FinderInterface;

    /**
     * @param  string          $table_name
     * @param  string          $field_name
     * @return FinderInterface
     */
    public function &joinTable($table_name, $field_name = null): FinderInterface;

    // ---------------------------------------------------
    //  Execution
    // ---------------------------------------------------

    /**
     * Return number of records that match the given criteria.
     *
     * @return int
     */
    public function count(): int;

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

    public function __toString(): string;
}
