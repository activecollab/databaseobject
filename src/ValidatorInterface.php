<?php
namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Exception\ValidationException;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ValidatorInterface
{
    /**
     * Check if value of $field_name is present
     *
     * @param  string  $field_name
     * @return boolean
     */
    public function present($field_name);

    /**
     * @param  string  $field_name
     * @return boolean
     */
    public function lowerThan($field_name);

    /**
     * @param  string  $field_name
     * @return boolean
     */
    public function greaterThan($field_name);

    /**
     * @param  string  $field_name
     * @param  string  ...$context
     * @return boolean
     */
    public function unique($field_name, ...$context);

    /**
     * @return bool
     */
    public function hasErrors();

    /**
     * @return ValidationException
     */
    public function createException();
}