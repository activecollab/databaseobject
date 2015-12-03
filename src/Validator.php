<?php
namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Exception\ValidationException;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use InvalidArgumentException;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Validator implements ValidatorInterface
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var string
     */
    private $table_name;

    /**
     * @var integer|null
     */
    private $object_id;

    /**
     * @var integer|null
     */
    private $old_object_id;

    /**
     * @var array
     */
    private $field_values;

    /**
     * @param ConnectionInterface $connection
     * @param string              $table_name
     * @param integer|null        $object_id
     * @param integer|null        $old_object_id
     * @param array               $field_values
     */
    public function __construct(ConnectionInterface $connection, $table_name, $object_id, $old_object_id, array $field_values)
    {
        $this->connection = $connection;
        $this->table_name = $table_name;
        $this->object_id = $object_id;
        $this->old_object_id = $old_object_id;
        $this->field_values = $field_values;
    }

    /**
     * Check if value of $field_name is present
     *
     * Note: strings are trimmed prior to check, and values that empty() would return true for (like '0') are consdiered
     * to be present (because we check strlen(trim($value)).
     *
     * @param  string  $field_name
     * @return boolean
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
     * Fail presence validation
     *
     * @param  string $field_name
     * @return bool
     */
    private function failPresenceValidation($field_name)
    {
        $this->addFieldError($field_name, "Value of '$field_name' is required");
        return false;
    }

    public function lowerThan($field_name)
    {
    }

    public function greaterThan($field_name)
    {
    }

    /**
     * Check if value that we are trying to save is unique in the given context
     *
     * Note: NULL is not checked for uniquenss because MySQL lets us save as many objects as we need with NULL without
     * raising an error
     *
     * @param  string                   $field_name
     * @param  string                   ...$context
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function unique($field_name, ...$context)
    {
        return $this->uniqueWhere($field_name, '', ...$context);
    }

    /**
     * Check if value that we are trying to save is unique in the given context
     *
     * Note: NULL is not checked for uniquenss because MySQL lets us save as many objects as we need with NULL without
     * raising an error
     *
     * @param  string                   $field_name
     * @param  array|string             $where
     * @param  string                   ...$context
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function uniqueWhere($field_name, $where, ...$context)
    {
        if (empty($field_name)) {
            throw new InvalidArgumentException("Value '$field_name' is not a valid field name");
        }

        if (empty($context) && (!array_key_exists($field_name, $this->field_values) || $this->field_values[$field_name] === null)) {
            return true; // NULL is always good for single column keys because MySQL does not check NULL for uniqueness
        }

        $field_names = [$field_name];

        if (count($context)) {
            $field_names = array_merge($field_names, $context);
        }

        // Check if we have existsing columns
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
            $sql = sprintf("SELECT COUNT(`id`) AS 'row_count' FROM $table_name WHERE $conditions");
        } else {
            $sql = $this->connection->prepare("SELECT COUNT(`id`) AS 'row_count' FROM $table_name WHERE ($conditions) AND (`id` != ?)", ($this->old_object_id ? $this->old_object_id : $this->object_id));
        }

        if ($this->connection->executeFirstCell($sql) > 0) {
            $this->addFieldError($field_name, "Value of '$field_name' needs to be unique");
            return false;
        }

        return true;
    }

    /**
     * Field value needs to be present and unique
     *
     * @param  string $field_name
     * @param  string ...$context
     * @return bool
     */
    public function presentAndUnique($field_name, ...$context)
    {
        if ($this->present($field_name)) {
            return $this->unique($field_name, ...$context);
        }

        return false;
    }

    /**
     * Field value needs to be present and unique
     *
     * @param  string       $field_name
     * @param  array|string $where
     * @param  string       ...$context
     * @return bool
     */
    public function presentAndUniqueWhere($field_name, $where, ...$context)
    {
        if ($this->present($field_name)) {
            return $this->uniqueWhere($field_name, $where, ...$context);
        }

        return false;
    }

    /**
     * Validate email address value
     *
     * @param  string     $field_name
     * @param  bool|false $allow_null
     * @return bool
     */
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

    /**
     * Validate URL value
     *
     * @param  string     $field_name
     * @param  bool|false $allow_null
     * @return bool
     */
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

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldErrors($field_name)
    {
        return isset($this->errors[$field_name]) ? $this->errors[$field_name] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function addFieldError($field_name, $error_message)
    {
        if (empty($this->errors[$field_name])) {
            $this->errors[$field_name] = [];
        }

        $this->errors[$field_name][] = $error_message;
    }

    /**
     * @return ValidationException
     */
    public function createException()
    {
        $message = 'Validation failed';

        $first_messages = [];
        $counter = 0;

        foreach ($this->errors as $field => $error_messages) {
            foreach ($error_messages as $error_message) {
                $first_messages[] = $error_message;
                $counter++;

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
