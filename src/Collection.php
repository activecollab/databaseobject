<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\Etag\EtagInterface;
use ActiveCollab\Etag\EtagInterface\Implementation as EtagInterfaceImplementation;
use LogicException;

/**
 * @package ActiveCollab\DatabaseObject
 */
abstract class Collection implements CollectionInterface
{
    use EtagInterfaceImplementation;

    /**
     * Construct the collection instance
     */
    public function __construct()
    {
        $this->configure();
    }

    /**
     * Pre-configure the collection when it is created
     */
    protected function configure()
    {
    }

    /**
     * Return true if ready
     *
     * If collection declares that it is not ready, but execute methods get called, we should throw an exception
     *
     * @return boolean
     */
    protected function isReady()
    {
        return true;
    }

    /**
     * @var string
     */
    private $application_identifier = 'APPv1.0';

    /**
     * Return application identifier
     *
     * @return string
     */
    public function getApplicationIdentifier()
    {
        return $this->application_identifier;
    }

    /**
     * Set application identifier
     *
     * @param  string $value
     * @return $this
     */
    public function &setApplicationIdentifier($value)
    {
        $this->application_identifier = $value;

        return $this;
    }

    /**
     * Prepare collection tag from bits of information
     *
     * @param  string $additional_identifier
     * @param  string $visitor_identifier
     * @param  string $hash
     * @return string
     */
    protected function prepareTagFromBits($additional_identifier, $visitor_identifier, $hash)
    {
        return '"' . implode(',', [$this->getApplicationIdentifier(), 'collection', get_class($this), $additional_identifier, $visitor_identifier, $hash]) . '"';
    }

    /**
     * Return true if this object can be tagged and cached on client side
     *
     * @return bool|null
     */
    public function canBeTagged()
    {
        return true;
    }

    // ---------------------------------------------------
    //  Pagination
    // ---------------------------------------------------

    /**
     * Set pagination configuration
     *
     * @param  integer $current_page
     * @param  integer $items_per_page
     * @return $this
     */
    public function &pagination($current_page = 1, $items_per_page = 100)
    {
        $this->is_paginated = true;

        $this->currentPage($current_page);

        $this->items_per_page = (int)$items_per_page;

        if ($this->items_per_page < 1) {
            $this->items_per_page = 100;
        }

        return $this;
    }

    /**
     * @var bool
     */
    private $is_paginated = false;

    /**
     * Return true if collection is paginated
     *
     * @return boolean
     */
    public function isPaginated()
    {
        return $this->is_paginated;
    }

    /**
     * @var integer|null
     */
    private $current_page = null;

    /**
     * @var integer|null
     */
    private $items_per_page = null;

    /**
     * Set current page
     *
     * @param  integer $value
     * @return $this
     */
    public function &currentPage($value)
    {
        if (!$this->is_paginated) {
            throw new LogicException('Page can be set only for paginated collections');
        }

        $this->current_page = (int) $value;

        if ($this->current_page < 1) {
            $this->current_page = 1;
        }

        return $this;
    }

    /**
     * Return current page #
     *
     * @return integer|null
     */
    public function getCurrentPage()
    {
        return $this->current_page;
    }

    /**
     * Return number of items that are displayed per page
     *
     * @return integer|null
     */
    public function getItemsPerPage()
    {
        return $this->items_per_page;
    }

    /**
     * Return array or property => value pairs that describes this object
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $result = $this->execute();

        if ($result instanceof ResultInterface) {
            return $result->jsonSerialize();
        } elseif ($result === null) {
            return [];
        } else {
            return $result;
        }
    }
}
