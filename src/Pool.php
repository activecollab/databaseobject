<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Exception\ObjectNotFoundException;
use ReflectionClass;
use LogicException;
use InvalidArgumentException;
use RuntimeException;

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
     * @var ObjectInterface[]
     */
    private $objects_pool = [];

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
     * @param  array|null      $attributes
     * @param  boolean         $save
     * @return ObjectInterface
     */
    public function &produce($type, array $attributes = null, $save = true)
    {
        if ($registered_type = $this->getRegisteredType($type)) {
            $object = $this->getProducerForRegisteredType($registered_type)->produce($type, $attributes, $save);

            if ($object instanceof ObjectInterface) {
                if ($object->isLoaded()) {
                    $this->objects_pool[$registered_type][$object->getId()] = $object;
                }

                return $object;
            } else {
                throw new RuntimeException("Failed to produce an instance of '$type'");
            }
        }

        throw new InvalidArgumentException("Can't produce an instance of '$type'");
    }

    /**
     * @var ProducerInterface
     */
    private $default_producer;

    /**
     * @var ProducerInterface[]
     */
    private $producers = [];

    /**
     * Return producer for registered type
     *
     * @param  string            $registered_type
     * @return ProducerInterface
     */
    protected function &getProducerForRegisteredType($registered_type)
    {
        if (empty($this->producers[$registered_type])) {
            if (empty($this->default_producer)) {
                $this->default_producer = new Producer($this->connection, $this);
            }

            return $this->default_producer;
        } else {
            return $this->producers[$registered_type];
        }
    }

    /**
     * Register producer instance for the given type
     *
     * @param string            $type
     * @param ProducerInterface $producer
     */
    public function registerProducer($type, ProducerInterface $producer)
    {
        if ($registered_type = $this->getRegisteredType($type)) {
            if (empty($this->producers[$registered_type])) {
                $this->producers[$registered_type] = $producer;
            } else {
                throw new LogicException("Producer for '$type' is already registered");
            }
        } else {
            throw new InvalidArgumentException("Type '$type' is not registered");
        }
    }

    /**
     * Register producerby providing a producer class name
     *
     * @param string $type
     * @param string $producer_class
     */
    public function registerProducerByClass($type, $producer_class)
    {
        if (class_exists($producer_class)) {
            $producer_class_reflection = new ReflectionClass($producer_class);

            if ($producer_class_reflection->implementsInterface(ProducerInterface::class)) {
                $this->registerProducer($type, new $producer_class($this->connection, $this));
            } else {
                throw new InvalidArgumentException("Class '$producer_class' does not implement '" . ProducerInterface::class . "' interface");
            }
        }
    }

    /**
     * Return object from object pool by the given type and ID; if object is not found, return NULL.
     *
     * @param  string                   $type
     * @param  integer                  $id
     * @param  boolean                  $use_cache
     * @return Object
     * @throws InvalidArgumentException
     */
    public function &getById($type, $id, $use_cache = true)
    {
        if ($registered_type = $this->getRegisteredType($type)) {
            $id = (integer) $id;

            if ($id < 1) {
                throw new InvalidArgumentException('ID is expected to be a number larger than 0');
            }

            if (isset($this->objects_pool[$registered_type][$id]) && $use_cache) {
                return $this->objects_pool[$registered_type][$id];
            } else {
                $type_fields = $this->getTypeFields($registered_type);

                if ($row = $this->connection->executeFirstRow($this->getSelectOneByType($registered_type), [$id])) {
                    $object_class = in_array('type', $type_fields) ? $row['type'] : $type;

                    /** @var ObjectInterface $object */
                    $object = new $object_class($this, $this->connection);
                    $object->loadFromRow($row);

                    return $this->addToObjectPool($registered_type, $id, $object);
                } else {
                    $object = null;

                    return $this->addToObjectPool($registered_type, $id, $object);
                }
            }
        }

        throw new InvalidArgumentException("Type '$type' is not registered");
    }

    /**
     * Return object from object pool by the given type and ID; if object is not found, raise an exception.
     *
     * @param  string                   $type
     * @param  integer                  $id
     * @param  boolean                  $use_cache
     * @return Object
     * @throws ObjectNotFoundException
     * @throws InvalidArgumentException
     */
    public function &mustGetById($type, $id, $use_cache = true)
    {
        $result = $this->getById($type, $id, $use_cache);

        if (empty($result)) {
            throw new ObjectNotFoundException($type, $id);
        }

        return $result;
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
        return $this->getById($type, $id, false);
    }

    /**
     * Check if object #ID of $type is in the pool
     *
     * @param  string  $type
     * @param  integer $id
     * @return boolean
     */
    public function isInPool($type, $id)
    {
        if ($registered_type = $this->getRegisteredType($type)) {
            $id = (integer)$id;

            if ($id < 1) {
                throw new InvalidArgumentException('ID is expected to be a number larger than 0');
            }

            return isset($this->objects_pool[$type][$id]);
        } else {
            throw new InvalidArgumentException("Type '$type' is not registered");
        }
    }

    /**
     * Add object to the object pool
     *
     * @param  string               $registered_type
     * @param  integer              $id
     * @param  ObjectInterface|null $value_to_store
     * @return ObjectInterface
     */
    private function &addToObjectPool($registered_type, $id, &$value_to_store)
    {
        if (empty($this->objects_pool[$registered_type])) {
            $this->objects_pool[$registered_type] = [];
        }

        $this->objects_pool[$registered_type][$id] = $value_to_store;

        return $this->objects_pool[$registered_type][$id];
    }

    /**
     * Add object to the pool
     *
     * @param ObjectInterface $object
     */
    public function remember(ObjectInterface &$object)
    {
        if ($object->isLoaded()) {
            if ($registered_type = $this->getRegisteredType(get_class($object))) {
                $this->addToObjectPool($registered_type, $object->getId(), $object);
            } else {
                throw new InvalidArgumentException("Type '" . get_class($object) . "' is not registered");
            }
        } else {
            throw new InvalidArgumentException('Object needs to be saved in the database to be remembered');
        }
    }

    /**
     * @param string $type
     * @param int    $id
     */
    public function forget($type, $id)
    {
        if ($registered_type = $this->getRegisteredType($type)) {
            $id = (integer)$id;

            if ($id < 1) {
                throw new InvalidArgumentException('ID is expected to be a number larger than 0');
            }

            if (isset($this->objects_pool[$type][$id])) {
                unset($this->objects_pool[$type][$id]);
            }
        } else {
            throw new InvalidArgumentException("Type '$type' is not registered");
        }
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
        if ($this->isTypeRegistered($type)) {
            if ($conditions = $this->connection->prepareConditions($conditions)) {
                return $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM ' . $this->getTypeTable($type, true) . " WHERE $conditions");
            } else {
                return $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM ' . $this->getTypeTable($type, true));
            }
        }

        throw new InvalidArgumentException("Type '$type' is not registered");
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
        if ($registered_type = $this->getRegisteredType($type)) {
            return new Finder($this, $this->connection, $registered_type);
        } else {
            throw new InvalidArgumentException("Type '$type' is not registered");
        }
    }

    /**
     * Return result by a prepared SQL statement
     *
     * @param  string                                 $type
     * @param  string                                 $sql
     * @param  mixed                                  $arguments
     * @return ResultInterface|ObjectInterface[]|null
     */
    public function findBySql($type, $sql, ...$arguments)
    {
        if (empty($type)) {
            throw new InvalidArgumentException('Type is required');
        }

        if (empty($sql)) {
            throw new InvalidArgumentException('SQL statement is required');
        }

        if ($registered_type = $this->getRegisteredType($type)) {
            if (in_array('type', $this->getTypeFields($type))) {
                $return_by = ConnectionInterface::RETURN_OBJECT_BY_FIELD;
                $return_by_value = 'type';
            } else {
                $return_by = ConnectionInterface::RETURN_OBJECT_BY_CLASS;
                $return_by_value = $type;
            }

            return $this->connection->advancedExecute($sql, $arguments, ConnectionInterface::LOAD_ALL_ROWS, $return_by, $return_by_value, [&$this, &$this->connection]);
        } else {
            throw new InvalidArgumentException("Type '$type' is not registered");
        }
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
        if ($registered_type = $this->getRegisteredType($type)) {
            if ($escaped) {
                if (empty($this->types[$registered_type]['escaped_table_name'])) {
                    $this->types[$registered_type]['escaped_table_name'] = $this->connection->escapeTableName($this->types[$registered_type]['table_name']);
                }

                return $this->types[$registered_type]['escaped_table_name'];
            } else {
                return $this->types[$registered_type]['table_name'];
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
        if ($registered_type = $this->getRegisteredType($type)) {
            return $this->types[$registered_type]['fields'];
        }

        throw new InvalidArgumentException("Type '$type' is not registered");
    }

    /**
     * Get a particular type property, and make it (using $callback) if it is not set already
     *
     * @param  string   $type
     * @param  string   $property
     * @param  callable $callback
     * @return mixed
     */
    public function getTypeProperty($type, $property, callable $callback)
    {
        if ($registered_type = $this->getRegisteredType($type)) {
            if (!array_key_exists($property, $this->types[$registered_type])) {
                $this->types[$registered_type][$property] = call_user_func($callback, $registered_type, $property);
            }

            return $this->types[$registered_type][$property];
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
        return $this->getTypeProperty($type, 'escaped_fields', function() use ($type) {
            $table_name = $this->getTypeTable($type, true);

            return implode(',', array_map(function($field_name) use ($table_name) {
                return $table_name . '.' . $this->connection->escapeFieldName($field_name);
            }, $this->getTypeFields($type)));
        });
    }

    /**
     * Return default order by for the given type
     *
     * @param  string   $type
     * @return string[]
     */
    public function getTypeOrderBy($type)
    {
        if ($registered_type = $this->getRegisteredType($type)) {
            return $this->types[$registered_type]['order_by'];
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
        return $this->getTypeProperty($type, 'escaped_order_by', function() use ($type) {
            $table_name = $this->getTypeTable($type, true);

            return implode(',', array_map(function($field_name) use ($table_name) {
                if (substr($field_name, 0, 1) == '!') {
                    return $table_name . '.' . $this->connection->escapeFieldName(substr($field_name, 1)) . ' DESC';
                } else {
                    return $table_name . '.' . $this->connection->escapeFieldName($field_name);
                }
            }, $this->getTypeOrderBy($type)));
        });
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

                if (empty($this->known_types[$type])) {
                    $this->known_types[$type] = null;
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
     * Note: $type does not need to be directly registered, because we need to support subclasses, which call can have
     * different traits impelemnted!
     *
     * @param  string $type
     * @return array
     */
    public function getTraitNamesByType($type)
    {
        if (empty($this->types[$type]['traits'])) {
            $this->types[$type]['traits'] = [];

            $this->recursiveGetTraitNames(new ReflectionClass($type), $this->types[ $type ]['traits']);
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
