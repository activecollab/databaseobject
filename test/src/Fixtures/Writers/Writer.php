<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\PoolInterface;
use ActiveCollab\DatabaseObject\ScrapInterface;
use ActiveCollab\DatabaseObject\ValidatorInterface;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\Russian;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\ClassicWriter;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
class Writer extends BaseWriter implements ScrapInterface
{
    use Russian, ClassicWriter;

    /**
     * @var mixed
     */
    public $custom_attribute_value;

    /**
     * @var bool
     */
    public $modified_using_custom_producer = false;

    /**
     * @var bool
     */
    public $is_scrapped = false;

    /**
     * @var bool
     */
    public $scrapped_using_custom_producer = false;

    /**
     * @param ConnectionInterface  $connection
     * @param PoolInterface        $pool
     * @param LoggerInterface|null $log
     */
    public function __construct(ConnectionInterface &$connection, PoolInterface &$pool, LoggerInterface &$log = null)
    {
        parent::__construct($connection, $pool, $log);

        $this->registerEventHandler('on_set_attribute', function($attribute, $value) {
            if ($attribute == 'custom_attribute') {
                $this->custom_attribute_value = $value;
            }
        });
    }

    /**
     * @var mixed
     */
    private $custom_field_value;

    /**
     * Return custom field value
     *
     * @return mixed
     */
    public function getCustomFieldValue()
    {
        return $this->custom_field_value;
    }

    /**
     * Set custom field value
     *
     * @param  mixed $value
     * @return $this
     */
    public function &setCustomFieldValue($value)
    {
        $this->custom_field_value = $value;

        return $this;
    }

    /**
     * @param ValidatorInterface $validator
     */
    public function validate(ValidatorInterface &$validator)
    {
        $validator->present('name');
        $validator->present('birthday');

        parent::validate($validator);
    }

    /**
     * Scrap the object, instead of permanently deleting it
     *
     * @param  bool|false $bulk
     * @return $this
     */
    public function &scrap($bulk = false)
    {
        $this->is_scrapped = true;

        return $this;
    }
}