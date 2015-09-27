<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use InvalidArgumentException;
use ReflectionClass;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Pool implements PoolInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Produce new instance of $type
     *
     * @param  string          $type
     * @return ObjectInterface
     */
    public function produce($type)
    {
        if ($this->isTypeRegistered($type)) {
            return new $type($this, $this->connection);
        }

        throw new InvalidArgumentException("Can't produce an instance of '$type'");
    }

    /**
     * @param  string  $type
     * @param  integer $id
     * @return Object
     */
    public function &getById($type, $id)
    {
        if ($registered_type = $this->getRegisteredType($type)) {
            $type_fields = $this->getTypeFields($registered_type);

            if ($row = $this->connection->executeFirstRow($this->getSelectOneByType($registered_type), [$id])) {
                $object_class = isset($type_fields['type']) ? $type_fields['type'] : $registered_type;

                /** @var Object $object */
                $object = new $object_class($this, $this->connection);
                $object->loadFromRow($row);

                return $object;
            } else {
                return null;
            }
        }

        throw new InvalidArgumentException("Type '$type' is not registered");
    }

    /**
     * Return number of records of the given type that match the given conditions
     *
     * @param  string            $type
     * @param  array|string|null $conditions
     * @return integer
     */
    public function count($type, $conditions = null)
    {
        if ($conditions = $this->connection->prepareConditions($conditions)) {
            return $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM ' . $this->getTypeTable($type, true) . " WHERE $conditions");
        } else {
            return $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM ' . $this->getTypeTable($type, true));
        }
    }

    /**
     * Return true if object of the given type with the given ID exists
     *
     * @param  string  $type
     * @param  integer $id
     * @return bool
     */
    public function exists($type, $id)
    {
        return (boolean) $this->count($type, ['`id` = ?', $id]);
    }

    /**
     * Find records by type
     *
     * @param  string $type
     * @return Finder
     */
    public function find($type)
    {
        return new Finder($this, $this->connection, $type);
    }

    /**
     * Reload an object of the give type with the given ID
     *
     * @param  string  $type
     * @param  integer $id
     * @return Object
     */
    public function &reload($type, $id)
    {
        return $this->getById($type, $id);
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

    /**
     * Return select by ID-s query for the given type
     *
     * @param  string $type
     * @return string
     */
    private function getSelectOneByType($type)
    {
        if (empty($this->types[$type]['sql_select_by_ids'])) {
            $this->types[$type]['sql_select_by_ids'] = 'SELECT ' . $this->getEscapedTypeFields($type) . ' FROM ' . $this->getTypeTable($type, true) . ' WHERE `id` IN ? ORDER BY `id`';
        }

        return $this->types[$type]['sql_select_by_ids'];
    }

    /**
     * Return a list of escaped field names for the given type
     *
     * @param  string $type
     * @return string
     */
    public function getEscapedTypeFields($type)
    {
        if (empty($this->types[$type]['escaped_fields'])) {
            $table_name = $this->getTypeTable($type, true);

            $this->types[$type]['escaped_fields'] = implode(',', array_map(function($field_name) use ($table_name) {
                return $table_name . '.' . $this->connection->escapeFieldName($field_name);
            }, $this->getTypeFields($type)));
        }

        return $this->types[$type]['escaped_fields'];
    }

    /**
     * Return default order by for the given type
     *
     * @param  string   $type
     * @return string[]
     */
    public function getTypeOrderBy($type)
    {
        if (isset($this->types[$type])) {
            return $this->types[$type]['order_by'];
        } else {
            throw new InvalidArgumentException("Type '$type' is not registered");
        }
    }

    /**
     * Return escaped list of fields that we can order by
     *
     * @param  string $type
     * @return string
     */
    public function getEscapedTypeOrderBy($type)
    {
        if (empty($this->types[$type]['escaped_order_by'])) {
            $table_name = $this->getTypeTable($type, true);

            $this->types[$type]['escaped_order_by'] = implode(',', array_map(function($field_name) use ($table_name) {
                if (substr($field_name, 0, 1) == '!') {
                    return $table_name . '.' . $this->connection->escapeFieldName(substr($field_name, 1)) . ' DESC';
                } else {
                    return $table_name . '.' . $this->connection->escapeFieldName($field_name);
                }
            }, $this->getTypeOrderBy($type)));
        }

        return $this->types[$type]['escaped_order_by'];
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
     * Cached type to registered type map
     *
     * @var array
     */
    private $known_types = [];

    /**
     * Return registered type for the given $type. This function is subclassing aware
     *
     * @param  string      $type
     * @return string|null
     */
    public function getRegisteredType($type)
    {
        $type = ltrim($type, '\\');

        if (empty($this->known_types[$type])) {
            if (isset($this->types[$type])) {
                $this->known_types[$type] = $type;
            } else {
                if (class_exists($type, true)) {
                    $reflection_class = new ReflectionClass($type);

                    if ($reflection_class->implementsInterface(ObjectInterface::class)) {
                        foreach ($this->types as $registered_type => $registered_type_properties) {
                            if ($reflection_class->isSubclassOf($registered_type)) {
                                $this->known_types[ $type ] = $registered_type;
                                break;
                            }
                        }
                    }
                }

                if (empty($this->known_types[ $type ])) {
                    $this->known_types[ $type ] = null;
                }
            }
        }

        return $this->known_types[$type];
    }

    /**
     * Return true if $type is registered
     *
     * @param  string $type
     * @return bool
     */
    public function isTypeRegistered($type)
    {
        return (boolean) $this->getRegisteredType($type);
    }

    /**
     * @param string[] $types
     */
    public function registerType(...$types)
    {
        foreach ($types as $type) {
            $type = ltrim($type, '\\');

            if (class_exists($type, true)) {
                $reflection = new ReflectionClass($type);

                if ($reflection->implementsInterface(ObjectInterface::class)) {
                    $default_properties = $reflection->getDefaultProperties();

                    if (empty($default_properties['order_by'])) {
                        $default_properties['order_by'] = '';
                    }

                    $this->types[$type] = [
                        'table_name' => $default_properties['table_name'],
                        'fields' => $default_properties['fields'],
                        'order_by' => $default_properties['order_by'],
                    ];
                } else {
                    throw new InvalidArgumentException("Type '$type' does not implement '" . ObjectInterface::class . "' interface");
                }
            } else {
                throw new InvalidArgumentException("Type '$type' is not defined");
            }
        }
    }

    /**
     * Return trait names by object
     *
     * @param  string $type
     * @return array
     */
    public function getTraitNamesByType($type)
    {
        if (empty($this->types[$type]['traits'])) {
            $this->types[$type]['traits'] = [];

            $this->recursiveGetTraitNames(new ReflectionClass($type), $this->types[$type]['traits']);
        }

        return $this->types[$type]['traits'];
    }

    /**
     * Recursively get trait names for the given class
     *
     * @param ReflectionClass $class
     * @param array           $trait_names
     */
    private function recursiveGetTraitNames(ReflectionClass $class, array &$trait_names)
    {
        $trait_names = array_merge($trait_names, $class->getTraitNames());

        if ($class->getParentClass()) {
            $this->recursiveGetTraitNames($class->getParentClass(), $trait_names);
        }
    }
}