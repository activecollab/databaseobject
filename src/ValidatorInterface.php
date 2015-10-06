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
     * Check if value that we are trying to save is unique in the given context
     *
     * @param  string       $field_name
     * @param  array|string $where
     * @param  string       ...$context
     */
    public function uniqueWhere($field_name, $where, ...$context);

    /**
     * Field value needs to be present and unique
     *
     * @param  string $field_name
     * @param  string ...$context
     * @return bool
     */
    public function presentAndUnique($field_name, ...$context);

    /**
     * Present and unique, with the given condition
     *
     * @param  string       $field_name
     * @param  array|string $where
     * @param  string       ...$context
     * @return boolean
     */
    public function presentAndUniqueWhere($field_name, $where, ...$context);

    /**
     * @return bool
     */
    public function hasErrors();

    /**
     * @return ValidationException
     */
    public function createException();
}