<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test\Fixtures\SpatialEntity\Base;

use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DatabaseObject\Entity\Entity;
use ActiveCollab\DatabaseObject\ValidatorInterface;

abstract class SpatialEntity extends Entity
{
    /**
     * Name of the table where records are stored.
     */
    protected string $table_name = 'spatial_entities';

    /**
     * Table fields that are managed by this entity.
     */
    protected array $entity_fields = [
        'id',
        'name',
        'polygon',
    ];

    /**
     * Table fields prepared for SELECT SQL query.
     */
    protected array $sql_read_statements = [
        '`spatial_entities`.`id`',
        '`spatial_entities`.`name`',
        'ST_GEOMFROMTEXT(`spatial_entities`.`polygon`) AS \'polygon\'',
    ];

    /**
     * Generated fields that are loaded, but not managed by the entity.
     *
     * @var array
     */
    protected array $generated_entity_fields = [];

    /**
     * List of default field values.
     */
    protected array $default_entity_field_values = [];

    /**
     * Return value of name field.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getFieldValue('name');
    }

    /**
     * Set value of name field.
     *
     * @param  string $value
     * @return $this
     */
    public function &setName($value)
    {
        $this->setFieldValue('name', $value);

        return $this;
    }

    /**
     * Return value of day field.
     *
     * @return \ActiveCollab\DateValue\DateValueInterface|null
     */
    public function getDay()
    {
        return $this->getFieldValue('day');
    }

    /**
     * Set value of day field.
     *
     * @param  \ActiveCollab\DateValue\DateValueInterface|null $value
     * @return $this
     */
    public function &setDay($value)
    {
        $this->setFieldValue('day', $value);

        return $this;
    }

    /**
     * Return value of is_used_on_day field.
     *
     * @return bool
     */
    public function isUsedOnDay()
    {
        return $this->getFieldValue('is_used_on_day');
    }

    /**
     * Return value of is_used_on_day field.
     *
     * @return bool
     * @deprecated use isUsedOnDay()
     */
    public function getIsUsedOnDay()
    {
        return $this->getFieldValue('is_used_on_day');
    }

    /**
     * Return value of plan_name field.
     *
     * @return string
     */
    public function getPlanName()
    {
        return $this->getFieldValue('plan_name');
    }

    /**
     * Return value of number_of_users field.
     *
     * @return int
     */
    public function getNumberOfUsers()
    {
        return $this->getFieldValue('number_of_users');
    }

    /**
     * Return value of stats field.
     *
     * @return mixed
     */
    public function getStats()
    {
        return $this->getFieldValue('stats');
    }

    /**
     * Set value of stats field.
     *
     * @param  mixed $value
     * @return $this
     */
    public function &setStats($value)
    {
        $this->setFieldValue('stats', $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldValue($field, $default = null)
    {
        $value = parent::getFieldValue($field, $default);

        if ($value === null) {
            return null;
        } else {
            switch ($field) {
                case 'stats':
                    return json_decode($value, true);
            }

            return $value;
        }
    }

    public function &setFieldValue($name, $value)
    {
        if ($value === null) {
            parent::setFieldValue($name, null);
        } else {
            switch ($name) {
                case 'id':
                case 'name':
                    return parent::setFieldValue($name, (string) $value);
                case 'day':
                    return parent::setFieldValue($name, $this->getDateTimeValueInstanceFrom($value));
                case 'stats':
                    return parent::setFieldValue($name, $this->isLoading() ? $value : json_encode($value));
                default:
                    if ($this->isLoading()) {
                        return parent::setFieldValue($name, $value);
                    } else {
                        if ($this->isGeneratedField($name)) {
                            throw new \LogicException("Generated field $name cannot be set by directly assigning a value");
                        } else {
                            throw new \InvalidArgumentException("Field $name does not exist in this table");
                        }
                    }
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            'account_id' => $this->getAccountId(),
            'day' => $this->getDay(),
            'is_used_on_day' => $this->getIsUsedOnDay(),
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(ValidatorInterface &$validator)
    {
        $validator->present('day');
        $validator->present('account_id');

        parent::validate($validator);
    }
}
