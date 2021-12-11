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
     * @param  string     $field_name
     * @param  int        $reference_value
     * @param  bool|false $allow_null
     * @return bool
     */
    public function lowerThan($field_name, $reference_value, $allow_null = false);

    /**
     * @param  string     $field_name
     * @param  int        $reference_value
     * @param  bool|false $allow_null
     * @return bool
     */
    public function lowerThanOrEquals($field_name, $reference_value, $allow_null = false);

    /**
     * @param  string     $field_name
     * @param  int        $reference_value
     * @param  bool|false $allow_null
     * @return bool
     */
    public function greaterThan($field_name, $reference_value, $allow_null = false);

    /**
     * @param  string     $field_name
     * @param  int        $reference_value
     * @param  bool|false $allow_null
     * @return bool
     */
    public function greaterThanOrEquals($field_name, $reference_value, $allow_null = false);

    public function inArray(
        string $field_name,
        array $array_of_values, bool $allow_null = false
    ): bool;

    public function unique(string $field_name, string ...$context): bool;

    /**
     * Check if value that we are trying to save is unique in the given context.
     */
    public function uniqueWhere(
        string $field_name,
        mixed $where,
        string ...$context
    ): bool;

    /**
     * Field value needs to be present and unique.
     *
     * @param  string   $field_name
     * @param  string[] $context
     * @return bool
     */
    public function presentAndUnique(string $field_name, string ...$context): bool;

    /**
     * Present and unique, with the given condition.
     *
     * @param  string       $field_name
     * @param  array|string $where
     * @param  string[]     $context
     * @return bool
     */
    public function presentAndUniqueWhere(
        string $field_name,
        mixed $where,
        string ...$context
    ): bool;

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
     */
    public function getErrors(): array;

    /**
     * Return true if there are error.
     */
    public function hasErrors(): bool;

    /**
     * Return field errors.
     */
    public function getFieldErrors(string $field_name): array;

    /**
     * Report an error for the given field.
     */
    public function addFieldError(string $field_name, string $error_message): void;
    public function createException(): ValidationException;
}
