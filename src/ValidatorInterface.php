<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Exception\ValidationException;

interface ValidatorInterface
{
    /**
     * Check if value of $field_name is present (not-empty).
     */
    public function present(string $field_name): bool;

    public function lowerThan(
        string $field_name,
        int $reference_value,
        bool $allow_null = false
    ): bool;

    public function lowerThanOrEquals(
        string $field_name,
        int $reference_value,
        bool $allow_null = false
    ): bool;

    public function greaterThan(
        string $field_name,
        int $reference_value,
        bool $allow_null = false
    ): bool;

    public function greaterThanOrEquals(
        string $field_name,
        int $reference_value,
        bool $allow_null = false
    ): bool;

    public function inArray(
        string $field_name,
        array $array_of_values, bool $allow_null = false
    ): bool;

    /**
     * Check if value that we are trying to save is unique in the given context.
     */
    public function unique(string $field_name, string ...$context): bool;

    /**
     * Check if value that we are trying to save is unique in the given context, with extra filtering.
     */
    public function uniqueWhere(
        string $field_name,
        mixed $where,
        string ...$context
    ): bool;

    /**
     * Field value needs to be present and unique.
     */
    public function presentAndUnique(string $field_name, string ...$context): bool;

    /**
     * Present and unique, with the given condition.
     */
    public function presentAndUniqueWhere(
        string $field_name,
        mixed $where,
        string ...$context
    ): bool;

    /**
     * Make sure that that only one record with the given value in the given context exists.
     *
     * Note: Difference with unique() validation is that there can other non-unique values, as long as there's not more
     * than one record with the given value.
     */
    public function onlyOne(
        string $field_name,
        mixed $field_value,
        string ...$context
    ): bool;

    /**
     * Make sure that that only one record with the given value in the given context exists, with extra filtering.
     */
    public function onlyOneWhere(
        string $field_name,
        mixed $field_value,
        mixed $where,
        string ...$context
    ): bool;

    /**
     * Validate email address value.
     */
    public function email(
        string $field_name,
        bool $allow_null = false
    ): bool;

    /**
     * Validate URL value.
     */
    public function url(
        string $field_name,
        bool $allow_null = false
    ): bool;

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
