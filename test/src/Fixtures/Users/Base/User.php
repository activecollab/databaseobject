<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Users\Base;

use ActiveCollab\DatabaseObject\Entity\Entity;
use ActiveCollab\DatabaseObject\ValidatorInterface;

abstract class User extends Entity
{
    /**
     * Name of the table where records are stored.
     */
    protected string $table_name = 'users';

    /**
     * All table fields.
     *
     * @var array
     */
    protected array $entity_fields = ['id', 'type', 'first_name', 'last_name', 'email', 'homepage_url', 'password'];

    /**
     * Table fields prepared for SELECT SQL query.
     */
    protected array $sql_read_statements = [
        '`users`.`id`',
        '`users`.`type`',
        '`users`.`first_name`',
        '`users`.`last_name`',
        '`users`.`email`',
        '`users`.`homepage_url`',
        '`users`.`password`',
    ];

    /**
     * List of default field values.
     */
    protected array $default_entity_field_values = [
        'type' => 'ActiveCollab\DatabaseObject\Test\Fixtures\Users\User',
        'first_name' => '',
        'last_name' => '',
    ];

    /**
     * Return value of type field.
     *
     * @return string
     */
    public function getType()
    {
        return $this->getFieldValue('type');
    }

    /**
     * Set value of type field.
     *
     * @param  string $value
     * @return $this
     */
    public function &settype($value)
    {
        $this->setFieldValue('type', $value);

        return $this;
    }

    /**
     * Return value of first_name field.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->getFieldValue('first_name');
    }

    /**
     * Set value of first_name  field.
     *
     * @param  string $value
     * @return $this
     */
    public function &setFirstName($value)
    {
        $this->setFieldValue('first_name', $value);

        return $this;
    }

    /**
     * Return value of last_name field.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->getFieldValue('last_name');
    }

    /**
     * Set value of last_name  field.
     *
     * @param  string $value
     * @return $this
     */
    public function &setLastName($value)
    {
        $this->setFieldValue('last_name', $value);

        return $this;
    }

    /**
     * Return value of email field.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->getFieldValue('email');
    }

    /**
     * Set value of email  field.
     *
     * @param  string $value
     * @return $this
     */
    public function &setEmail($value)
    {
        $this->setFieldValue('email', $value);

        return $this;
    }

    /**
     * Return value of homepage_url field.
     *
     * @return string
     */
    public function getHomepageUrl()
    {
        return $this->getFieldValue('homepage_url');
    }

    /**
     * Set value of homepage_url field.
     *
     * @param  string $value
     * @return $this
     */
    public function &setHomepageUrl($value)
    {
        $this->setFieldValue('homepage_url', $value);

        return $this;
    }

    /**
     * Return value of password field.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->getFieldValue('password');
    }

    /**
     * Set value of password  field.
     *
     * @param  string $value
     * @return $this
     */
    public function &setPassword($value)
    {
        $this->setFieldValue('password', $value);

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
                    return parent::setFieldValue($field, (int) $value);
                case 'type':
                case 'first_name':
                case 'last_name':
                case 'email':
                case 'homepage_url':
                case 'password':
                    return parent::setFieldValue($field, (string) $value);
                default:
                    throw new \InvalidArgumentException("Field $field does not exist in this table");
            }
        }

        return $this;
    }

    /**
     * Validate object properties before object is saved.
     */
    public function validate(ValidatorInterface $validator): ValidatorInterface
    {
        $validator->presentAndUnique('email');
        $validator->present('password');

        return parent::validate($validator);
    }
}
