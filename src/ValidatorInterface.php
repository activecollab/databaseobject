<?php
namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Exception\ValidationException;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ValidatorInterface
{
    public function notEmpty($field_name);

    public function isEmpty($field_name);

    public function lowerThan($field_name);

    public function greaterThan($field_name);

    /**
     * @return bool
     */
    public function hasErrors();

    /**
     * @return ValidationException
     */
    public function createException();
}