<?php

namespace ActiveCollab\DatabaseObject\Exception;

use Exception;
use ActiveCollab\DatabaseObject\Object;

class ValidationException extends Exception
{
    // Any field
    const ANY_FIELD = '-- any --';

    /**
     * Object instance
     *
     * @var DataObject
     */
    private $object;

    /**
     * Errors array
     *
     * @var array
     */
    private $errors = [];

    /**
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'Validation failed';
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Return parent object instance
     *
     * @return Object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param Object $object
     */
    public function setObject(Object $object)
    {
        $this->object = $object;
    }

    /**
     * Return array or property => value pairs that describes this object
     * @return array
     */
    public function jsonSerialize()
    {
        $result = [ 'message' => $this->getMessage(), 'type' => get_class($this), 'field_errors' => [] ];

        foreach ($this->getErrors() as $field => $messages) {
            foreach ($messages as $message) {
                if (empty($result['field_errors'][$field])) {
                    $result['field_errors'][$field] = [];
                }

                $result['field_errors'][$field][] = $message;
            }
        }

        if ($this->object instanceof Object) {
            $result['object_class'] = get_class($this->object);
        }

        return $result;
    }

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Return number of errors from specific form
     *
     * @return array
     */
    public function getErrors()
    {
        return count($this->errors) ? $this->errors : null;
    }

    /**
     * Return field errors
     *
     * @param  string $field
     * @return array
     */
    public function getFieldErrors($field)
    {
        return isset($this->errors[$field]) ? $this->errors[$field] : null;
    }

    /**
     * Returns true if there are error messages reported
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return (boolean) count($this->errors);
    }

    /**
     * Check if a specific field has reported errors
     *
     * @param  string  $field
     * @return boolean
     */
    public function hasError($field)
    {
        return isset($this->errors[$field]) && count($this->errors[$field]);
    }

    /**
     * Add error to array
     *
     * @param string $error Error message
     * @param string $field
     */
    public function addError($error, $field = self::ANY_FIELD)
    {
        if (empty($field)) {
            $field = self::ANY_FIELD;
        }

        if (empty($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $error;
    }
}