<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\Etag\EtagInterface;
use JsonSerializable;

/**
 * @package ActiveCollab\DatabaseObject\Collection
 */
interface CollectionInterface extends EtagInterface, JsonSerializable
{
    /**
     * Return application identifier
     *
     * @return string
     */
    public function getApplicationIdentifier();

    /**
     * Set application identifier
     *
     * @param  string $value
     * @return string
     */
    public function &setApplicationIdentifier($value);

    /**
     * Run the query and return DB result
     *
     * @return ResultInterface|ObjectInterface[]
     */
    public function execute();

    /**
     * Return ID-s of matching records
     *
     * @return array
     */
    public function executeIds();

    /**
     * Return number of records that match conditions set by the collection
     *
     * @return integer
     */
    public function count();

    /**
     * Return current page #
     *
     * @return integer|null
     */
    public function getCurrentPage();

    /**
     * Return number of items that are displayed per page
     *
     * @return integer|null
     */
    public function getItemsPerPage();

    /**
     * Set pagination configuration
     *
     * @param  integer $current_page
     * @param  integer $items_per_page
     * @return $this
     */
    public function &setPagination($current_page = 1, $items_per_page = 100);
}
