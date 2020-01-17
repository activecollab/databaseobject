<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\Etag\EtagInterface;
use JsonSerializable;

/**
 * @package ActiveCollab\DatabaseObject\Collection
 */
interface CollectionInterface extends EtagInterface, JsonSerializable
{
    /**
     * Return application identifier.
     *
     * @return string
     */
    public function getApplicationIdentifier();

    /**
     * Set application identifier.
     *
     * @param  string $value
     * @return $this
     */
    public function &setApplicationIdentifier($value);

    public function canBeTagged(): bool;

    /**
     * Run the query and return DB result.
     *
     * @return ResultInterface|EntityInterface[]
     */
    public function execute();

    /**
     * Return ID-s of matching records.
     *
     * @return array
     */
    public function executeIds();

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count();

    /**
     * Set pagination configuration.
     *
     * @param  int   $current_page
     * @param  int   $items_per_page
     * @return $this
     */
    public function &pagination($current_page = 1, $items_per_page = 100);

    /**
     * Return true if collection is paginated.
     *
     * @return bool
     */
    public function isPaginated();

    /**
     * Set current page.
     *
     * @param  int   $value
     * @return $this
     */
    public function &currentPage($value);

    /**
     * Return current page #.
     *
     * @return int|null
     */
    public function getCurrentPage();

    /**
     * Return number of items that are displayed per page.
     *
     * @return int|null
     */
    public function getItemsPerPage();
}
