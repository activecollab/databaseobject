<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface FinderInterface
{
    /**
     * @return string
     */
    public function getType();

    // ---------------------------------------------------
    //  Configuration
    // ---------------------------------------------------

    /**
     * Set finder conditions.
     *
     * @param string $pattern
     * @param  mixed  ...$arguments
     * @return $this
     */
    public function &where($pattern, ...$arguments);

    /**
     * @param  string $order_by
     * @return $this
     */
    public function &orderBy($order_by);

    /**
     * @param  int   $offset
     * @param  int   $limit
     * @return $this
     */
    public function &limit($offset, $limit);

    /**
     * @param  string $type
     * @param  string $field_name
     * @return $this
     */
    public function &join($type, $field_name = null);

    /**
     * @param  string $table_name
     * @param  string $field_name
     * @return $this
     */
    public function &joinTable($table_name, $field_name = null);

    // ---------------------------------------------------
    //  Execution
    // ---------------------------------------------------

    /**
     * Return number of records that match the given criteria.
     *
     * @return int
     */
    public function count();

    /**
     * Return all records that match the given criteria.
     *
     * @return \ActiveCollab\DatabaseConnection\Result\Result|EntityInterface[]|null
     */
    public function all();

    /**
     * Return first record that matches the given criteria.
     *
     * @return EntityInterface|null
     */
    public function first();

    /**
     * Return array of ID-s that match the given criteria.
     *
     * @return int[]
     */
    public function ids();

    /**
     * Prepare SQL and load one or more records.
     *
     * @return mixed
     */
    public function execute();
}
