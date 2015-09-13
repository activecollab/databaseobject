<?php
namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Exception\ValidationException;

class Validator
{
    private $errors = [];

    public function notEmpty($field_name)
    {

    }

    public function isEmpty($field_name)
    {

    }

    public function lowerThan($field_name)
    {

    }

    public function greaterThan($field_name)
    {

    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * @return ValidationException
     */
    public function createException()
    {
        return new ValidationException();
    }
}