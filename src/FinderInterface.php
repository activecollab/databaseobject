<?php

namespace ActiveCollab\DatabaseObject;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface FinderInterface
{
    /**
     * Set finder conditions
     *
     * @param  string $pattern
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
     * @param  integer $offset
     * @param  integer $limit
     * @return $this
     */
    public function &limit($offset, $limit);

    /**
     * Return number of records that match the given criteria
     *
     * @return integer
     */
    public function count();

    /**
     * Return all records that match the given criteria
     *
     * @return \ActiveCollab\DatabaseConnection\Result\Result|ObjectInterface[]|null
     */
    public function all();

    /**
     * Return first record that matches the given criteria
     *
     * @return ObjectInterface|null
     */
    public function first();

    /**
     * Return array of ID-s that match the given criteria
     *
     * @return integer[]
     */
    public function ids();

    /**
     * Prepare SQL and load one or more records
     *
     * @return mixed
     */
    public function execute();

    /**
     * @return string
     */
    public function getType();
}
