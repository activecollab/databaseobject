<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Exception\ValidationException;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ValidatorInterface
{
    /**
     * Check if value of $field_name is present.
     *
     * @param  string $field_name
     * @return bool
     */
    public function present($field_name);

    /**
     * @param  string $field_name
     * @return bool
     */
    public function lowerThan($field_name);

    /**
     * @param  string $field_name
     * @return bool
     */
    public function greaterThan($field_name);

    /**
     * @param string $field_name
     * @param  string  ...$context
     * @return bool
     */
    public function unique($field_name, ...$context);

    /**
     * Check if value that we are trying to save is unique in the given context.
     *
     * @param string       $field_name
     * @param array|string $where
     * @param  string       ...$context
     */
    public function uniqueWhere($field_name, $where, ...$context);

    /**
     * Field value needs to be present and unique.
     *
     * @param string $field_name
     * @param  string ...$context
     * @return bool
     */
    public function presentAndUnique($field_name, ...$context);

    /**
     * Present and unique, with the given condition.
     *
     * @param string       $field_name
     * @param array|string $where
     * @param  string       ...$context
     * @return bool
     */
    public function presentAndUniqueWhere($field_name, $where, ...$context);

    /**
     * Validate email address value.
     *
     * @param  string     $field_name
     * @param  bool|false $allow_null
     * @return bool
     */
    public function email($field_name, $allow_null = false);

    /**
     * Return array of validation messages, indexed by field name.
     *
     * @return array
     */
    public function getErrors();

    /**
     * Return true if there are error.
     *
     * @return bool
     */
    public function hasErrors();

    /**
     * Return field errors.
     *
     * @param  string $field_name
     * @return array
     */
    public function getFieldErrors($field_name);

    /**
     * Report an error for the given field.
     *
     * @param string $field_name
     * @param string $error_message
     */
    public function addFieldError($field_name, $error_message);

    /**
     * @return ValidationException
     */
    public function createException();
}
