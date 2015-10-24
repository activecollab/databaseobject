<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\PoolInterface;
use ActiveCollab\DatabaseObject\ValidatorInterface;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\Russian;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\ClassicWriter;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
class Writer extends BaseWriter
{
    use Russian, ClassicWriter;

    /**
     * @var mixed
     */
    public $custom_attribute_value;

    /**
     * @param PoolInterface       $pool
     * @param ConnectionInterface $connection
     */
    public function __construct(PoolInterface $pool, ConnectionInterface $connection)
    {
        parent::__construct($pool, $connection);

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
}