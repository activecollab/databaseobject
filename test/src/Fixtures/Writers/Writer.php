<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\PoolInterface;
use ActiveCollab\DatabaseObject\ScrapInterface;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\ClassicWriter;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\Russian;
use ActiveCollab\DatabaseObject\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * @property string $is_special
 *
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

    public function __construct(ConnectionInterface &$connection, PoolInterface &$pool, LoggerInterface $logger)
    {
        parent::__construct($connection, $pool, $logger);

        $this->registerEventHandler('on_set_attribute', function ($attribute, $value) {
            if ($attribute == 'custom_attribute') {
                $this->custom_attribute_value = $value;
            }
        });

        $this->registerEventHandler('on_json_serialize', function (array &$result) {
            $result['name'] = $this->getName();
            $result['birthday'] = $this->getBirthday();
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributes()
    {
        return array_merge(parent::getAttributes(), ['custom_field_value']);
    }

    /**
     * @var mixed
     */
    private $custom_field_value;

    /**
     * Return custom field value.
     *
     * @return mixed
     */
    public function getCustomFieldValue()
    {
        return $this->custom_field_value;
    }

    /**
     * Set custom field value.
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
     * @var string
     */
    private $protected_custom_field_value = 'protected';

    /**
     * Return protected field value.
     *
     * @return string
     */
    public function getProtectedCustomFieldValue()
    {
        return $this->protected_custom_field_value;
    }

    /**
     * Set protected custom field value.
     *
     * @param  mixed $value
     * @return $this
     */
    public function &setProtectedCustomFieldValue($value)
    {
        $this->protected_custom_field_value = $value;

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
     * Scrap the object, instead of permanently deleting it.
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
