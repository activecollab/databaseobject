<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test\Fixtures\StatSnapshots\Base;

use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DatabaseObject\Entity\Entity;
use ActiveCollab\DatabaseObject\ValidatorInterface;

abstract class StatsSnapshot extends Entity
{
    /**
     * Name of the table where records are stored.
     */
    protected string $table_name = 'stats_snapshots';

    /**
     * Table fields that are managed by this entity.
     *
     * @var array
     */
    protected array $entity_fields = ['id', 'account_id', 'day', 'stats'];

    /**
     * Table fields prepared for SELECT SQL query.
     */
    protected array $sql_read_statements = [
        '`stats_snapshots`.`id`',
        '`stats_snapshots`.`account_id`',
        '`stats_snapshots`.`day`',
        '`stats_snapshots`.`stats`',
    ];

    /**
     * Generated fields that are loaded, but not managed by the entity.
     *
     * @var array
     */
    protected array $generated_entity_fields = ['is_used_on_day', 'plan_name', 'number_of_users'];

    /**
     * List of default field values.
     */
    protected array $default_entity_field_values = [];

    protected function configure(): void
    {
        $this->setGeneratedFieldsValueCaster(
            new ValueCaster(
                [
                    'is_used_on_day' => ValueCasterInterface::CAST_BOOL,
                    'plan_name' => ValueCasterInterface::CAST_STRING,
                    'number_of_users' => ValueCasterInterface::CAST_INT,
                ]
            )
        );
    }

    /**
     * Return value of account_id field.
     *
     * @return int
     */
    public function getAccountId()
    {
        return $this->getFieldValue('account_id');
    }

    /**
     * Set value of account_id field.
     *
     * @param  int   $value
     * @return $this
     */
    public function &setAccountId($value)
    {
        $this->setFieldValue('account_id', $value);

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

    public function getFieldValue(string $field, mixed $default = null): mixed
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

    public function setFieldValue(string $field, mixed $value): static
    {
        if ($value === null) {
            parent::setFieldValue($field, null);
        } else {
            switch ($field) {
                case 'id':
                case 'account_id':
                    return parent::setFieldValue($field, (int) $value);
                case 'day':
                    return parent::setFieldValue($field, $this->getDateTimeValueInstanceFrom($value));
                case 'stats':
                    return parent::setFieldValue($field, $this->isLoading() ? $value : json_encode($value));
                default:
                    if ($this->isLoading()) {
                        return parent::setFieldValue($field, $value);
                    } else {
                        if ($this->isGeneratedField($field)) {
                            throw new \LogicException("Generated field $field cannot be set by directly assigning a value");
                        } else {
                            throw new \InvalidArgumentException("Field $field does not exist in this table");
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

    public function validate(ValidatorInterface $validator): ValidatorInterface
    {
        $validator->present('day');
        $validator->present('account_id');

        return parent::validate($validator);
    }
}
