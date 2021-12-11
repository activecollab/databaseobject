<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\Exception\ValidationException;
use InvalidArgumentException;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Validator implements ValidatorInterface
{
    private array $errors = [];
    private ConnectionInterface $connection;
    private string $table_name;
    private ?int $object_id;
    private ?int $old_object_id;
    private array $field_values;

    public function __construct(
        ConnectionInterface $connection,
        string $table_name,
        ?int $object_id,
        ?int $old_object_id,
        array $field_values
    )
    {
        $this->connection = $connection;
        $this->table_name = $table_name;
        $this->object_id = $object_id;
        $this->old_object_id = $old_object_id;
        $this->field_values = $field_values;
    }

    /**
     * Check if value of $field_name is present.
     *
     * Note: strings are trimmed prior to check, and values that empty() would return true for (like '0') are consdiered
     * to be present (because we check strlen(trim($value)).
     *
     * @param  string $field_name
     * @return bool
     */
    public function present($field_name)
    {
        if (empty($field_name)) {
            throw new InvalidArgumentException("Value '$field_name' is not a valid field name");
        }

        if (array_key_exists($field_name, $this->field_values)) {
            if (is_string($this->field_values[$field_name])) {
                if (mb_strlen(trim($this->field_values[$field_name])) > 0) {
                    return true;
                } else {
                    return $this->failPresenceValidation($field_name);
                }
            } elseif (is_bool($this->field_values[$field_name])) {
                return true;
            } else {
                if (empty($this->field_values[$field_name])) {
                    return $this->failPresenceValidation($field_name);
                } else {
                    return true;
                }
            }
        } else {
            return $this->failPresenceValidation($field_name);
        }
    }

    /**
     * Fail presence validation.
     *
     * @param  string $field_name
     * @return bool
     */
    private function failPresenceValidation($field_name)
    {
        $this->addFieldError($field_name, "Value of '$field_name' is required");

        return false;
    }

    public function lowerThan($field_name, $reference_value, $allow_null = false)
    {
        return $this->compareValues($field_name, $reference_value, $allow_null, function ($a, $b) {
            return $a < $b;
        }, "Value of '$field_name' is not lower than $reference_value");
    }

    public function lowerThanOrEquals($field_name, $reference_value, $allow_null = false)
    {
        return $this->compareValues($field_name, $reference_value, $allow_null, function ($a, $b) {
            return $a <= $b;
        }, "Value of '$field_name' is not lower than or equal to $reference_value");
    }

    public function greaterThan($field_name, $reference_value, $allow_null = false)
    {
        return $this->compareValues($field_name, $reference_value, $allow_null, function ($a, $b) {
            return $a > $b;
        }, "Value of '$field_name' is not greater than $reference_value");
    }

    public function greaterThanOrEquals($field_name, $reference_value, $allow_null = false)
    {
        return $this->compareValues($field_name, $reference_value, $allow_null, function ($a, $b) {
            return $a >= $b;
        }, "Value of '$field_name' is not greater than or equal to $reference_value");
    }

    /**
     * Validate field value by comparing it to a reference value using a closure.
     */
    protected function compareValues(
        string $field_name,
        mixed $reference_value,
        bool $allow_null,
        callable $compare_with,
        string $validation_failed_message
    ): bool
    {
        if (empty($field_name)) {
            throw new InvalidArgumentException("Value '$field_name' is not a valid field name");
        }

        if (array_key_exists($field_name, $this->field_values)) {
            if ($this->field_values[$field_name] === null) {
                if ($allow_null) {
                    return true;
                } else {
                    return $this->failPresenceValidation($field_name);
                }
            }

            if (call_user_func($compare_with, $this->field_values[$field_name], $reference_value)) {
                return true;
            } else {
                $this->addFieldError($field_name, $validation_failed_message);

                return false;
            }
        } else {
            return $this->failPresenceValidation($field_name);
        }
    }

    public function inArray(
        string $field_name,
        array $array_of_values, bool $allow_null = false
    ): bool
    {
        if (empty($field_name)) {
            throw new InvalidArgumentException("Value '$field_name' is not a valid field name");
        }

        if (array_key_exists($field_name, $this->field_values)) {
            if ($this->field_values[$field_name] === null) {
                if ($allow_null) {
                    return true;
                } else {
                    return $this->failPresenceValidation($field_name);
                }
            }

            if (in_array($this->field_values[$field_name], $array_of_values)) {
                return true;
            } else {
                $this->addFieldError($field_name, "Value of '$field_name' is not present in the list of supported values");

                return false;
            }
        } else {
            return $this->failPresenceValidation($field_name);
        }
    }

    public function unique(string $field_name, string ...$context): bool
    {
        return $this->uniqueWhere($field_name, '', ...$context);
    }

    public function uniqueWhere(
        string $field_name,
        mixed $where,
        string ...$context
    ): bool
    {
        if (empty($field_name)) {
            throw new InvalidArgumentException("Value '$field_name' is not a valid field name");
        }

        if (empty($context) && (!array_key_exists($field_name, $this->field_values) || $this->field_values[$field_name] === null)) {
            return true; // NULL is always good for single column keys because MySQL does not check NULL for uniqueness
        }

        $field_names = [
            $field_name,
        ];

        if (!empty($context)) {
            $field_names = array_merge($field_names, $context);
        }

        // Check if we have existing columns.
        foreach ($field_names as $v) {
            if (!array_key_exists($v, $this->field_values)) {
                throw new InvalidArgumentException("Field '$v' is not known");
            }
        }

        $table_name = $this->connection->escapeTableName($this->table_name);

        $conditions = [];

        if ($where) {
            $conditions[] = $this->connection->prepareConditions($where);
        }

        foreach ($field_names as $v) {
            $escaped_field_name = $this->connection->escapeFieldName($v);

            if ($this->field_values[$v] === null) {
                $conditions[] = "$escaped_field_name IS NULL";
            } else {
                $conditions[] = $this->connection->prepare("$escaped_field_name = ?", $this->field_values[$v]);
            }
        }

        $conditions = implode(' AND ', $conditions);

        if (empty($this->object_id)) {
            $sql = sprintf("SELECT COUNT(`id`) AS 'row_count' FROM %s WHERE %s", $table_name, $conditions);
        } else {
            $sql = $this->connection->prepare(
                sprintf(
                    "SELECT COUNT(`id`) AS 'row_count' FROM %s WHERE (%s) AND (`id` != ?)",
                    $table_name,
                    $conditions
                ),
                $this->old_object_id ? $this->old_object_id : $this->object_id
            );
        }

        if ($this->connection->executeFirstCell($sql) === 0) {
            return true;
        }

        if (empty($context)) {
            $this->addFieldError(
                $field_name,
                sprintf("Value of '%s' needs to be unique", $field_name)
            );

            return false;
        }

        $this->addFieldError(
            $field_name,
            sprintf(
                "Value of '%s' needs to be unique in context of %s",
                $field_name,
                implode(
                    ', ',
                    array_map(
                        function ($field_name) {
                            return "'$field_name'";
                        },
                        $context
                    )
                )
            )
        );

        return false;
    }

    public function presentAndUnique(string $field_name, string ...$context): bool
    {
        if ($this->present($field_name)) {
            return $this->unique($field_name, ...$context);
        }

        return false;
    }

    public function presentAndUniqueWhere(
        string $field_name,
        mixed $where,
        string ...$context
    ): bool
    {
        if ($this->present($field_name)) {
            return $this->uniqueWhere($field_name, $where, ...$context);
        }

        return false;
    }

    public function onlyOneWhere(
        string $field_name,
        mixed $field_value,
        mixed $where,
        string ...$context
    ): bool
    {
        if (empty($field_name)) {
            throw new InvalidArgumentException(
                sprintf("Value '%s' is not a valid field name", $field_name)
            );
        }

        return false;
    }

    public function email($field_name, $allow_null = false)
    {
        if (array_key_exists($field_name, $this->field_values)) {
            if ($this->field_values[$field_name] === null) {
                if ($allow_null) {
                    return true;
                } else {
                    return $this->failPresenceValidation($field_name);
                }
            }

            if (filter_var($this->field_values[$field_name], FILTER_VALIDATE_EMAIL)) {
                return true;
            } else {
                $this->addFieldError($field_name, "Value of '$field_name' is not a valid email address");

                return false;
            }
        } else {
            return $this->failPresenceValidation($field_name);
        }
    }

    public function url($field_name, $allow_null = false)
    {
        if (array_key_exists($field_name, $this->field_values)) {
            if ($this->field_values[$field_name] === null) {
                if ($allow_null) {
                    return true;
                } else {
                    return $this->failPresenceValidation($field_name);
                }
            }

            if (filter_var($this->field_values[$field_name], FILTER_VALIDATE_URL)) {
                return true;
            } else {
                $this->addFieldError($field_name, "Value of '$field_name' is not a valid URL");

                return false;
            }
        } else {
            return $this->failPresenceValidation($field_name);
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function getFieldErrors(string $field_name): array
    {
        return $this->errors[$field_name] ?? [];
    }

    public function addFieldError(string $field_name, string $error_message): void
    {
        if (empty($this->errors[$field_name])) {
            $this->errors[$field_name] = [];
        }

        $this->errors[$field_name][] = $error_message;
    }

    public function createException(): ValidationException
    {
        $message = 'Validation failed';

        $first_messages = [];
        $counter = 0;

        foreach ($this->errors as $field => $error_messages) {
            foreach ($error_messages as $error_message) {
                $first_messages[] = $error_message;
                ++$counter;

                if ($counter > 3) {
                    break 2;
                }
            }
        }

        if (!empty($first_messages)) {
            $message .= ': ' . implode(', ', $first_messages);
        }

        return (new ValidationException($message))->setErrors($this->errors);
    }
}
