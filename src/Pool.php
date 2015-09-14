<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Connection;

use InvalidArgumentException;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Pool
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return true if object of the given type with the given ID exists
     *
     * @param  Object|string $type_or_sample_object
     * @param  integer       $id
     * @return bool
     */
    public function exists($type_or_sample_object, $id)
    {
        $type = $type_or_sample_object instanceof Object ? get_class($type_or_sample_object) : $type_or_sample_object;

        return (boolean) $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM ' . $this->getTypeTable($type, true) . ' WHERE `id` = ?', $id);
    }

    /**
     * @param  string  $type
     * @param  integer $id
     * @return Object
     */
    public function getById($type, $id)
    {
        $type_fields = $this->getTypeFields($type);

        if ($row = $this->connection->executeFirstRow('SELECT ' . $this->getEscapedTypeFields($type) . ' FROM ' . $this->getTypeTable($type, true) . ' WHERE id = ?', $id)) {
            $object_class = isset($type_fields['type']) ? $type_fields['type'] : $type;

            /** @var Object $object */
            $object = new $object_class($this, $this->connection);
            $object->loadFromRow($row);

            return $object;
        }

        return null;
    }

    /**
     * Return table name by type
     *
     * @param  string  $type
     * @param  boolean $escaped
     * @return string
     */
    public function getTypeTable($type, $escaped = false)
    {
        if (isset($this->types[$type])) {
            if ($escaped) {
                if (empty($this->types[$type]['escaped_table_name'])) {
                    $this->types[$type]['escaped_table_name'] = $this->connection->escapeTableName($this->types[$type]['table_name']);
                }

                return $this->types[$type]['escaped_table_name'];
            } else {
                return $this->types[$type]['table_name'];
            }
        } else {
            throw new InvalidArgumentException("Type '$type' is not registered");
        }
    }

    /**
     * @param  string $type
     * @return array
     */
    public function getTypeFields($type)
    {
        if (isset($this->types[$type])) {
            return $this->types[$type]['fields'];
        } else {
            throw new InvalidArgumentException("Type '$type' is not registered");
        }
    }

    public function getEscapedTypeFields($type)
    {
        if (empty($this->types[$type]['escaped_fields'])) {
            $this->types[$type]['escaped_fields'] = implode(',', array_map(function($field_name) {
                return $this->connection->escapeFieldName($field_name);
            }, $this->getTypeFields($type)));
        }

        return $this->types[$type]['escaped_fields'];
    }

    /**
     * @var array
     */
    private $types = [];

    /**
     * @return array
     */
    public function getRegisteredTypes()
    {
        return array_keys($this->types);
    }

    /**
     * Return true if $type is registered
     *
     * @param  string $type
     * @return bool
     */
    public function isTypeRegistered($type)
    {
        return !empty($this->types[$type]);
    }

    /**
     * @param string[] $types
     */
    public function registerType(...$types)
    {
        foreach ($types as $type) {
            if (class_exists($type, true)) {
                $reflection = new \ReflectionClass($type);

                if ($reflection->isSubclassOf(Object::class)) {
                    $default_properties = $reflection->getDefaultProperties();

                    $this->types[$type] = [
                        'table_name' => $default_properties['table_name'],
                        'fields' => $default_properties['fields'],
                    ];
                } else {
                    throw new InvalidArgumentException("Type '$type' is not a subclass of '" . Object::class . "'");
                }
            } else {
                throw new InvalidArgumentException("Type '$type' is not defined");
            }
        }
    }
}