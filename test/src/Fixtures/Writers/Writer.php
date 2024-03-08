<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\DatabaseObject\PoolInterface;
use ActiveCollab\DatabaseObject\ScrapInterface;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\ClassicWriter;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\Russian;
use ActiveCollab\DatabaseObject\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * @property string $is_special
 */
class Writer extends BaseWriter implements ScrapInterface
{
    use Russian, ClassicWriter;

    /**
     * @var mixed
     */
    public $custom_attribute_value;
    public bool $modified_using_custom_producer = false;
    public bool $is_scrapped = false;
    public bool $scrapped_using_custom_producer = false;

    public function __construct(ConnectionInterface &$connection, PoolInterface &$pool, LoggerInterface $logger)
    {
        parent::__construct($connection, $pool, $logger);

        $this->registerEventHandler(
            'on_set_attribute',
            function ($attribute, $value) {
                if ($attribute == 'custom_attribute') {
                    $this->custom_attribute_value = $value;
                }
            },
        );

        $this->registerEventHandler(
            'on_json_serialize',
            function (array &$result) {
                $result['name'] = $this->getName();
                $result['birthday'] = $this->getBirthday();
            },
        );
    }

    protected function getAttributes(): array
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
    public function validate(ValidatorInterface $validator): ValidatorInterface
    {
        $validator->present('name');
        $validator->present('birthday');

        return parent::validate($validator);
    }

    public function scrap(bool $bulk = false): EntityInterface
    {
        $this->is_scrapped = true;

        return $this;
    }
}
