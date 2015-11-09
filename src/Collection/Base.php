<?php

namespace ActiveCollab\DatabaseObject\Collection;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\ObjectInterface;
use ActiveCollab\Etag\EtagInterface;
use ActiveCollab\Etag\EtagInterface\Implementation as EtagInterfaceImplementation;
use JsonSerializable;

/**
* Foundation of all data collections
*
* @package angie.library.database
*/
abstract class Base implements EtagInterface, JsonSerializable
{
    use EtagInterfaceImplementation;

    /**
     * Collection name
     *
     * @var string
     */
    private $name;

    /**
     * Construct the collection
     *
     * @param string $name
     */
    public function __construct($name)
    {
      $this->name = $name;
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

    /**
     * Prepare collection tag from bits of information
     *
     * @param  string $user_email
     * @param  string $hash
     * @return string
     */
    protected function prepareTagFromBits($user_email, $hash)
    {
      return '"' . implode(',', [ APPLICATION_VERSION, 'collection', $this->getModelName(), $this->getName(), $user_email, $hash ]) . '"';
    }

    /**
     * Return collection name
     *
     * @return string
     */
    public function getName()
    {
      return $this->name;
    }

    /**
     * @var int|null
     */
    private $current_page = null;

    /**
     * @var int|null
     */
    private $items_per_page = null;

    /**
     * Return current page #
     *
     * @return int
     */
    public function getCurrentPage()
    {
      return $this->current_page;
    }

    /**
     * Return number of items that are displayed per page
     *
     * @return int
     */
    public function getItemsPerPage()
    {
      return $this->items_per_page;
    }

    /**
     * Set pagination configuration
     *
     * @param int $current_page
     * @param int $items_per_page
     */
    public function setPagination($current_page = 1, $items_per_page = 100)
    {
        $this->current_page = (int) $current_page;

        if ($this->current_page < 1) {
            $this->current_page = 1;
        }

        $this->items_per_page = (int) $items_per_page;

        if ($this->items_per_page < 1) {
            $this->items_per_page = 100;
        }
    }

    /**
     * Return model name
     *
     * @return string
     */
    abstract public function getModelName();

    /**
     * Run the query and return DB result
     *
     * @return ResultInterface|ObjectInterface[]
     */
    abstract public function execute();

    /**
     * Return number of records that match conditions set by the collection
     *
     * @return integer
     */
    abstract public function count();
}
