<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Entity\Entity;
use ActiveCollab\DateValue\DateValueInterface;
use InvalidArgumentException;

abstract class BaseWriter extends Entity implements WriterInterface
{
    /**
     * Name of the table where records are stored.
     */
    protected string $table_name = 'writers';

    /**
     * All table fields.
     *
     * @var array
     */
    protected array $entity_fields = ['id', 'name', 'birthday', 'created_at', 'updated_at'];

    /**
     * Table fields prepared for SELECT SQL query.
     */
    protected array $sql_read_statements = [
        '`writers`.`id`',
        '`writers`.`name`',
        '`writers`.`birthday`',
        '`writers`.`created_at`',
        '`writers`.`updated_at`',
    ];

    /**
     * List of default field values.
     *
     * @var array
     */
    protected array $default_entity_field_values = ['name' => 'Unknown Writer'];

    /**
     * Name of AI field (if any).
     *
     * @var string
     */
    protected string $auto_increment = 'id';

    /**
     * @var string[]
     */
    protected array $order_by = ['!id'];

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
     * Return value of birthday field.
     *
     * @return DateValueInterface
     */
    public function getBirthday()
    {
        return $this->getFieldValue('birthday');
    }

    /**
     * Set value of birthday field.
     *
     * @param  DateValueInterface $value
     * @return $this
     */
    public function &setBirthday($value)
    {
        $this->setFieldValue('birthday', $value);

        return $this;
    }

    /**
     * Return value of created_at field.
     *
     * @return \ActiveCollab\DateValue\DateTimeValueInterface|null
     */
    public function getCreatedAt()
    {
        return $this->getFieldValue('created_at');
    }

    /**
     * Set value of created_at  field.
     *
     * @param  \ActiveCollab\DateValue\DateTimeValueInterface|null $value
     * @return $this
     */
    public function &setCreatedAt($value)
    {
        $this->setFieldValue('created_at', $value);

        return $this;
    }

    /**
     * Return value of updated_at field.
     *
     * @return \ActiveCollab\DateValue\DateTimeValueInterface|null
     */
    public function getUpdatedAt()
    {
        return $this->getFieldValue('updated_at');
    }

    /**
     * Set value of updated_at  field.
     *
     * @param  \ActiveCollab\DateValue\DateTimeValueInterface|null $value
     * @return $this
     */
    public function &setUpdatedAt($value)
    {
        $this->setFieldValue('updated_at', $value);

        return $this;
    }

    /**
     * Set value of specific field.
     */
    public function setFieldValue(string $field, mixed $value): static
    {
        if ($value === null) {
            parent::setFieldValue($field, null);
        } else {
            switch ($field) {
                case 'id':
                    parent::setFieldValue($field, (int) $value);
                    break;
                case 'name':
                    parent::setFieldValue($field, (string) $value);
                    break;
                case 'birthday':
                    parent::setFieldValue($field, $this->getDateValueInstanceFrom($value));
                    break;
                case 'created_at':
                case 'updated_at':
                    return parent::setFieldValue($field, $this->getDateTimeValueInstanceFrom($value));
                default:
                    throw new InvalidArgumentException("'$field' is not a known field");
            }
        }

        return $this;
    }
}
