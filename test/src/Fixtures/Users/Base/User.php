<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Users\Base;

use ActiveCollab\DatabaseObject\Object;
use ActiveCollab\DatabaseObject\ValidatorInterface;

/**
 * @package ActiveCollab\Id\Model\Base
 */
abstract class User extends Object
{
    /**
     * Name of the table where records are stored.
     *
     * @var string
     */
    protected $table_name = 'users';

    /**
     * All table fields.
     *
     * @var array
     */
    protected $fields = ['id', 'type', 'first_name', 'last_name', 'email', 'homepage_url', 'password'];

    /**
     * List of default field values.
     *
     * @var array
     */
    protected $default_field_values = [
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
    public function &setType($value)
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
     *
     * @param  string                    $name
     * @param  mixed                     $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function &setFieldValue($name, $value)
    {
        if ($value === null) {
            parent::setFieldValue($name, null);
        } else {
            switch ($name) {
                case 'id':
                    return parent::setFieldValue($name, (integer) $value);
                case 'type':
                case 'first_name':
                case 'last_name':
                case 'email':
                case 'homepage_url':
                case 'password':
                    return parent::setFieldValue($name, (string) $value);
                default:
                    throw new \InvalidArgumentException("Field $name does not exist in this table");
            }
        }

        return $this;
    }

    /**
     * Validate object properties before object is saved.
     *
     * @param ValidatorInterface $validator
     */
    public function validate(ValidatorInterface &$validator)
    {
        $validator->presentAndUnique('email');
        $validator->present('password');

        parent::validate($validator);
    }
}
