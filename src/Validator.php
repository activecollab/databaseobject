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
     * @param  string                   $field_name
     * @param  string                   ...$context
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function unique($field_name, ...$context)
    {
        if (empty($field_name)) {
            throw new InvalidArgumentException("Value '$field_name' is not a valid field name");
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

        foreach ($field_names as $v) {
            $conditions[] = $this->connection->prepare("$v = ?", $this->field_values[$v]);
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
     * Return array of validation messages, indexed by field name
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * Return field errors
     *
     * @param  string $field_name
     * @return array
     */
    public function getFieldErrors($field_name)
    {
        return isset($this->errors[$field_name]) ? $this->errors[$field_name] : [];
    }

    /**
     * Report an error for the given field
     *
     * @param string $field_name
     * @param string $error_message
     */
    private function addFieldError($field_name, $error_message)
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
        return new ValidationException();
    }
}