<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Entity\Entity;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\DateValue\DateValueInterface;
use InvalidArgumentException;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
abstract class BaseWriter extends Entity implements EntityInterface
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
     *
     * @param  string $name
     * @param  mixed  $value
     * @return mixed
     */
    public function &setFieldValue($name, $value)
    {
        if ($value === null) {
            parent::setFieldValue($name, null);
        } else {
            switch ($name) {
                case 'id':
                    parent::setFieldValue($name, (int) $value);
                    break;
                case 'name':
                    parent::setFieldValue($name, (string) $value);
                    break;
                case 'birthday':
                    parent::setFieldValue($name, $this->getDateValueInstanceFrom($value));
                    break;
                case 'created_at':
                case 'updated_at':
                    return parent::setFieldValue($name, $this->getDateTimeValueInstanceFrom($value));
                default:
                    throw new InvalidArgumentException("'$name' is not a known field");
            }
        }

        return $this;
    }
}
