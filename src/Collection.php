<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\Etag\EtagInterface;
use ActiveCollab\Etag\EtagInterface\Implementation as EtagInterfaceImplementation;

/**
 * @package ActiveCollab\DatabaseObject
 */
abstract class Collection implements CollectionInterface
{
    use EtagInterfaceImplementation;

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
     * @return string
     */
    public function &setApplicationIdentifier($value)
    {
        $this->application_identifier = $value;

        return $this;
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

    /**
     * Prepare collection tag from bits of information
     *
     * @param  string $visitor_identifier
     * @param  string $hash
     * @return string
     */
    protected function prepareTagFromBits($visitor_identifier, $hash)
    {
        return '"' . implode(',', [$this->getApplicationIdentifier(), 'collection', get_class($this), $visitor_identifier, $hash]) . '"';
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
     * Set pagination configuration
     *
     * @param  integer $current_page
     * @param  integer $items_per_page
     * @return $this
     */
    public function &setPagination($current_page = 1, $items_per_page = 100)
    {
        $this->current_page = (int)$current_page;

        if ($this->current_page < 1) {
            $this->current_page = 1;
        }

        $this->items_per_page = (int)$items_per_page;

        if ($this->items_per_page < 1) {
            $this->items_per_page = 100;
        }

        return $this;
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
