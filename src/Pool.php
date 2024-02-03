<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\ContainerAccess\ContainerAccessInterface;
use ActiveCollab\ContainerAccess\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\DatabaseObject\Exception\ObjectNotFoundException;
use ActiveCollab\DatabaseObject\TraitsResolver\TraitsResolver;
use ActiveCollab\DatabaseObject\TraitsResolver\TraitsResolverInterface;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;

class Pool implements PoolInterface, ProducerInterface, ContainerAccessInterface
{
    use ContainerAccessInterfaceImplementation;

    protected ConnectionInterface $connection;
    protected LoggerInterface $logger;

    /**
     * @var EntityInterface[]
     */
    private array $objects_pool = [];

    public function __construct(ConnectionInterface $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function produce(
        string $type,
        array $attributes = null,
        bool $save = true,
    ): EntityInterface
    {
        $registered_type = $this->requireRegisteredType($type);

        $object = $this
            ->getProducerForRegisteredType($registered_type)
                ->produce($type, $attributes, $save);

        if ($object->isLoaded()) {
            $this->objects_pool[$registered_type][$object->getId()] = $object;
        }

        return $object;
    }

    public function modify(
        EntityInterface $instance,
        array $attributes = null,
        bool $save = true,
    ): EntityInterface
    {
        if ($instance->isNew()) {
            throw new RuntimeException('Only objects that are saved to database can be modified');
        }

        $registered_type = $this->requireRegisteredType(get_class($instance));

        $instance = $this
            ->getProducerForRegisteredType($registered_type)
                ->modify($instance, $attributes, $save);

        $this->objects_pool[$registered_type][$instance->getId()] = $instance;

        return $instance;
    }

    public function scrap(
        EntityInterface $instance,
        bool $force_delete = false,
    ): EntityInterface
    {
        if ($instance->isNew()) {
            throw new RuntimeException('Only objects that are saved to database can be modified');
        }

        $registered_type = $this->requireRegisteredType(get_class($instance));

        $instance_id = $instance->getId();
        $instance = $this->getProducerForRegisteredType($registered_type)->scrap($instance, $force_delete);

        if ($instance->isNew() && !empty($this->objects_pool[$registered_type][$instance_id])) {
            unset($this->objects_pool[$registered_type][$instance_id]);
        }

        return $instance;
    }

    private string $default_producer_class = Producer::class;

    public function getDefaultProducerClass(): string
    {
        return $this->default_producer_class;
    }

    public function setDefaultProducerClass(string $default_producer_class): PoolInterface
    {
        if (!class_exists($default_producer_class)) {
            throw new InvalidArgumentException('Producer class not found.');
        }

        if (!(new ReflectionClass($default_producer_class))->implementsInterface(ProducerInterface::class)) {
            throw new InvalidArgumentException('Producer class does not implement producer interface.');
        }

        $this->default_producer_class = $default_producer_class;
        $this->default_producer = null;

        return $this;
    }

    private ?ProducerInterface $default_producer = null;

    public function getDefaultProducer(): ProducerInterface
    {
        if (empty($this->default_producer)) {
            $default_producer_class = $this->getDefaultProducerClass();

            $this->default_producer = new $default_producer_class($this->connection, $this, $this->logger);

            if ($this->default_producer instanceof ContainerAccessInterface && $this->hasContainer()) {
                $this->default_producer->setContainer($this->getContainer());
            }
        }

        return $this->default_producer;
    }

    public function setDefaultProducer(ProducerInterface $producer): PoolInterface
    {
        $this->default_producer = $producer;

        return $this;
    }

    /**
     * @var ProducerInterface[]
     */
    private array $producers = [];

    protected function getProducerForRegisteredType(string $registered_type): ProducerInterface
    {
        if (empty($this->producers[$registered_type])) {
            return $this->getDefaultProducer();
        }

        return $this->producers[$registered_type];
    }

    public function registerProducer(string $type, ProducerInterface $producer): PoolInterface
    {
        $registered_type = $this->requireRegisteredType($type);

        if (empty($this->producers[$registered_type])) {
            if ($producer instanceof ContainerAccessInterface && $this->hasContainer()) {
                $producer->setContainer($this->getContainer());
            }

            $this->producers[$registered_type] = $producer;
        } else {
            throw new LogicException(sprintf("Producer for '%s' is already registered", $type));
        }

        return $this;
    }

    public function registerProducerByClass(string $type, string $producer_class): PoolInterface
    {
        if (class_exists($producer_class)) {
            $producer_class_reflection = new ReflectionClass($producer_class);

            if ($producer_class_reflection->implementsInterface(ProducerInterface::class)) {
                $this->registerProducer($type, new $producer_class($this->connection, $this, $this->logger));
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        "Class '%s' does not implement '%s' interface",
                        $producer_class,
                        ProducerInterface::class
                    )
                );
            }
        }

        return $this;
    }

    public function getById(string $type, int $id, bool $use_cache = true): ?EntityInterface
    {
        $registered_type = $this->requireRegisteredType($type);

        if ($id < 1) {
            throw new InvalidArgumentException('ID is expected to be a number larger than 0');
        }

        if (isset($this->objects_pool[$registered_type][$id]) && $use_cache) {
            return $this->objects_pool[$registered_type][$id];
        }

        $row = $this->connection->executeFirstRow($this->getSelectOneByType($registered_type), [$id]);

        if (empty($row)) {
            $object = null;

            return $this->addToObjectPool($registered_type, $id, $object);
        }

        return $this->getObjectFromRow(
            $type,
            $registered_type,
            $row,
        );
    }

    public function mustGetById(string $type, int $id, bool $use_cache = true): EntityInterface
    {
        $result = $this->getById($type, $id, $use_cache);

        if (empty($result)) {
            throw new ObjectNotFoundException($type, $id);
        }

        return $result;
    }

    public function getFirstBy(string $type, string $where, mixed ...$arguments): ?EntityInterface
    {
        if (empty($where)) {
            throw new LogicException('Conditions are required');
        }

        $registered_type = $this->requireRegisteredType($type);

        $row = $this->connection->executeFirstRow(
            sprintf(
                'SELECT %s FROM %s WHERE %s LIMIT 0, 1',
                $this->getTypeFieldsReadStatement($type),
                $this->getTypeTable($type, true),
                $this->connection->prepare($where, $arguments),
            )
        );

        if (empty($row)) {
            return null;
        }

        return $this->getObjectFromRow(
            $type,
            $registered_type,
            $row,
        );
    }

    private function getObjectFromRow(
        string $type,
        string $registered_type,
        array $row,
    ): EntityInterface
    {
        $object_class = in_array('type', $this->getTypeFields($registered_type))
            ? $row['type']
            : $type;

        /** @var object|EntityInterface $object */
        $object = new $object_class($this->connection, $this, $this->logger);

        if ($object instanceof ContainerAccessInterface && $this->hasContainer()) {
            $object->setContainer($this->getContainer());
        }

        $object->loadFromRow($row);

        return $this->addToObjectPool($registered_type, $object->getId(), $object);
    }

    public function reload(string $type, int $id): ?EntityInterface
    {
        return $this->getById($type, $id, false);
    }

    public function isInPool(string $type, int $id): bool
    {
        $this->requireRegisteredType($type);

        if ($id < 1) {
            throw new InvalidArgumentException('ID is expected to be a number larger than 0');
        }

        return isset($this->objects_pool[$type][$id]);
    }

    private function &addToObjectPool(
        string $registered_type,
        int $id,
        ?EntityInterface $value_to_store
    ): ?EntityInterface
    {
        if (empty($this->objects_pool[$registered_type])) {
            $this->objects_pool[$registered_type] = [];
        }

        $this->objects_pool[$registered_type][$id] = $value_to_store;

        return $this->objects_pool[$registered_type][$id];
    }

    public function remember(EntityInterface $object): void
    {
        if (!$object->isLoaded()) {
            throw new InvalidArgumentException('Object needs to be saved in the database to be remembered');
        }

        $this->addToObjectPool(
            $this->requireRegisteredType(get_class($object)),
            $object->getId(),
            $object
        );
    }
    public function forget(string $type, int ...$ids_to_forget): void
    {
        $this->requireRegisteredType($type);

        foreach ($ids_to_forget as $id_to_forget) {
            if ($id_to_forget < 1) {
                throw new InvalidArgumentException('ID is expected to be a number larger than 0');
            }

            if (isset($this->objects_pool[$type][$id_to_forget])) {
                unset($this->objects_pool[$type][$id_to_forget]);
            }
        }
    }

    /**
     * Return number of records of the given type that match the given conditions.
     *
     * @param array|string|null $conditions
     */
    public function count(string $type, mixed $conditions = null): int
    {
        $this->requireRegisteredType($type);

        $conditions = $this->connection->prepareConditions($conditions);

        if ($conditions) {
            return $this->connection->executeFirstCell(
                sprintf('SELECT COUNT(`id`) AS "row_count" FROM %s WHERE %s',
                    $this->getTypeTable($type, true),
                    $conditions
                )
            );
        }

        return $this->connection->executeFirstCell(
            sprintf('SELECT COUNT(`id`) AS "row_count" FROM %s', $this->getTypeTable($type, true))
        );
    }

    /**
     * Return true if object of the given type with the given ID exists.
     *
     * @param  string $type
     * @param  int    $id
     * @return bool
     */
    public function exists(string $type, int $id): bool
    {
        return (bool) $this->count($type, ['`id` = ?', $id]);
    }

    /**
     * Find records by type.
     *
     * @param  string $type
     * @return Finder
     */
    public function find(string $type): FinderInterface
    {
        $registered_type = $this->requireRegisteredType($type);

        $default_finder_class = $this->getDefaultFinderClass();

        /** @var Finder $finder */
        $finder = new $default_finder_class($this->connection, $this, $this->logger, $registered_type);

        if ($finder instanceof ContainerAccessInterface && $this->hasContainer()) {
            $finder->setContainer($this->getContainer());
        }

        return $finder;
    }

    public function getDefaultFinderClass(): string
    {
        return Finder::class;
    }

    public function findBySql(string $type, string $sql, mixed ...$arguments): mixed
    {
        if (empty($type)) {
            throw new InvalidArgumentException('Type is required');
        }

        if (empty($sql)) {
            throw new InvalidArgumentException('SQL statement is required');
        }

        $this->requireRegisteredType($type);

        if (in_array('type', $this->getTypeFields($type))) {
            $return_by = ConnectionInterface::RETURN_OBJECT_BY_FIELD;
            $return_by_value = 'type';
        } else {
            $return_by = ConnectionInterface::RETURN_OBJECT_BY_CLASS;
            $return_by_value = $type;
        }

        if ($this->hasContainer()) {
            return $this->connection->advancedExecute(
                $sql,
                $arguments,
                ConnectionInterface::LOAD_ALL_ROWS,
                $return_by,
                $return_by_value,
                [
                    &$this->connection,
                    &$this,
                    &$this->logger,
                ],
                $this->getContainer()
            );
        }

        return $this->connection->advancedExecute(
            $sql,
            $arguments,
            ConnectionInterface::LOAD_ALL_ROWS,
            $return_by,
            $return_by_value,
            [
                &$this->connection,
                &$this,
                &$this->logger,
            ]
        );
    }

    public function getTypeTable(string $type, bool $escaped = false): string
    {
        $registered_type = $this->requireRegisteredType($type);

        if ($escaped) {
            if (empty($this->types[$registered_type]['escaped_table_name'])) {
                $this->types[$registered_type]['escaped_table_name'] = $this->connection->escapeTableName($this->types[$registered_type]['table_name']);
            }

            return $this->types[$registered_type]['escaped_table_name'];
        } else {
            return $this->types[$registered_type]['table_name'];
        }
    }

    public function getTypeFields(string $type): array
    {
        return $this->types[$this->requireRegisteredType($type)]['fields'];
    }

    public function getGeneratedTypeFields(string $type): array
    {
        return $this->types[$this->requireRegisteredType($type)]['generated_fields'];
    }

    public function getTypeSqlReadStatements(string $type): array
    {
        return $this->types[$this->requireRegisteredType($type)]['sql_read_statements'];
    }

    public function getTypeProperty(string $type, string $property, callable $callback): mixed
    {
        $registered_type = $this->requireRegisteredType($type);

        if (!array_key_exists($property, $this->types[$registered_type])) {
            $this->types[$registered_type][$property] = call_user_func($callback, $registered_type, $property);
        }

        return $this->types[$registered_type][$property];
    }

    /**
     * Return select by ID-s query for the given type.
     */
    private function getSelectOneByType(string $type): string
    {
        if (empty($this->types[$type]['sql_select_by_ids'])) {
            $this->types[$type]['sql_select_by_ids'] = sprintf(
                'SELECT %s FROM %s WHERE `id` IN ? ORDER BY `id`',
                $this->getTypeFieldsReadStatement($type),
                $this->getTypeTable($type, true)
            );
        }

        return $this->types[$type]['sql_select_by_ids'];
    }

    public function getEscapedTypeFields(string $type): string
    {
        return $this->getTypeProperty(
            $type,
            'escaped_fields',
            function () use ($type) {
                $table_name = $this->getTypeTable($type, true);

                $escaped_field_names = [];

                foreach ($this->getTypeFields($type) as $field_name) {
                    $escaped_field_names[] = $table_name . '.' . $this->connection->escapeFieldName($field_name);
                }

                foreach ($this->getGeneratedTypeFields($type) as $field_name) {
                    $escaped_field_names[] = $table_name . '.' . $this->connection->escapeFieldName($field_name);
                }

                return implode(',', $escaped_field_names);
            }
        );
    }

    public function getTypeFieldsReadStatement(string $type): string
    {
        return $this->getTypeProperty(
            $type,
            'field_read_statements',
            function () use ($type) {
                $table_name = $this->getTypeTable($type, true);

                $field_read_statements = [];

                foreach ($this->getTypeSqlReadStatements($type) as $sql_read_statement) {
                    $field_read_statements[] = $sql_read_statement;
                }

                foreach ($this->getGeneratedTypeFields($type) as $field_name) {
                    $field_read_statements[] = $table_name . '.' . $this->connection->escapeFieldName($field_name);
                }

                return implode(',', $field_read_statements);
            }
        );
    }

    public function getTypeOrderBy(string $type): array
    {
        return $this->types[$this->requireRegisteredType($type)]['order_by'];
    }

    public function getEscapedTypeOrderBy(string $type): string
    {
        return $this->getTypeProperty(
            $type,
            'escaped_order_by',
            function () use ($type) {
                $table_name = $this->getTypeTable($type, true);

                return implode(
                    ',',
                    array_map(
                        function ($field_name) use ($table_name) {
                            if (str_starts_with($field_name, '!')) {
                                return $table_name . '.' . $this->connection->escapeFieldName(substr($field_name, 1)) . ' DESC';
                            }

                            return $table_name . '.' . $this->connection->escapeFieldName($field_name);
                        },
                        $this->getTypeOrderBy($type)
                    )
                );
            }
        );
    }

    private array $types = [];

    public function getRegisteredTypes(): array
    {
        return array_keys($this->types);
    }

    /**
     * Cached type to registered type map.
     */
    private array $known_types = [];

    /**
     * Return registered type for the given $type. This function is subclassing aware.
     */
    public function getRegisteredType(string $type): ?string
    {
        $type = ltrim($type, '\\');

        if (empty($this->known_types[$type])) {
            if (isset($this->types[$type])) {
                $this->known_types[$type] = $type;
            } else {
                if (class_exists($type)) {
                    $reflection_class = new ReflectionClass($type);

                    if ($reflection_class->implementsInterface(EntityInterface::class)) {
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

    public function requireRegisteredType(string $type): string
    {
        $registered_type = $this->getRegisteredType($type);

        if (empty($registered_type)) {
            throw new InvalidArgumentException("Type '$type' is not registered");
        }

        return $registered_type;
    }

    public function isTypeRegistered(string $type): bool
    {
        return (bool) $this->getRegisteredType($type);
    }

    private ?string $polymorph_type_interface = null;

    public function getPolymorphTypeInterface(): ?string
    {
        return $this->polymorph_type_interface;
    }

    public function &setPolymorphTypeInterface(?string $value): PoolInterface
    {
        $this->polymorph_type_interface = $value;

        return $this;
    }

    private array $polymorph_types = [];

    /**
     * Return true if $type is polymorph (has type column that is used to figure out a class of individual record).
     */
    public function isTypePolymorph(string $type): bool
    {
        $registered_type = $this->getRegisteredType($type);

        if (!array_key_exists($registered_type, $this->polymorph_types)) {

            // Use polymorph interface to detect type.
            if ($this->getPolymorphTypeInterface()) {
                $this->polymorph_types[$registered_type] = (new ReflectionClass($registered_type))
                    ->implementsInterface($this->polymorph_type_interface);

            // Check for type field (legacy).
            } else {
                $this->polymorph_types[$registered_type] = in_array('type', $this->getTypeFields($type));
            }
        }

        return $this->polymorph_types[$registered_type];
    }

    public function registerType(string ...$types): PoolInterface
    {
        foreach ($types as $type) {
            $type = ltrim($type, '\\');

            if (!class_exists($type)) {
                throw new InvalidArgumentException(sprintf("Type '%s' is not defined.", $type));
            }

            $reflection = new ReflectionClass($type);

            if (!$reflection->implementsInterface(EntityInterface::class)) {
                throw new InvalidArgumentException(
                    sprintf(
                        "Type '%s' does not implement '%s' interface.",
                        $type,
                        EntityInterface::class
                    )
                );
            }

            $default_properties = $reflection->getDefaultProperties();

            if (empty($default_properties['order_by'])) {
                $default_properties['order_by'] = '';
            }

            $this->types[$type] = [
                'table_name' => $default_properties['table_name'],
                'fields' => $default_properties['entity_fields'],
                'generated_fields' => $default_properties['generated_entity_fields'],
                'sql_read_statements' => $default_properties['sql_read_statements'],
                'order_by' => $default_properties['order_by'],
            ];
        }

        return $this;
    }

    private ?TraitsResolverInterface $traits_resolver = null;

    private function getTraitsResolver(): TraitsResolverInterface
    {
        if (empty($this->traits_resolver)) {
            $this->traits_resolver = new TraitsResolver();
        }

        return $this->traits_resolver;
    }

    /**
     * Return trait names by object.
     *
     * Note: $type does not need to be directly registered, because we need to support subclasses, which call can have
     * different traits implemented!
     */
    public function getTraitNamesByType(string $type): array
    {
        return $this->getTraitsResolver()->getClassTraits($type);
    }
}
