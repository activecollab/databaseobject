<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Collection;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Collection;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use Doctrine\Inflector\InflectorFactory;
use InvalidArgumentException;
use LogicException;

abstract class Type extends Collection
{
    private ?string $registered_type = null;

    /**
     * Return type that this collection works with.
     */
    abstract public function getType(): string;

    /**
     * Return registered type.
     */
    protected function getRegisteredType(): string
    {
        if ($this->registered_type === null) {
            $this->registered_type = $this->pool->getRegisteredType($this->getType());

            if (empty($this->registered_type)) {
                throw new InvalidArgumentException(
                    sprintf("Type '%s' is not registered", $this->getType())
                );
            }
        }

        return $this->registered_type;
    }

    // ---------------------------------------------------
    //  Etag
    // ---------------------------------------------------

    /**
     * Return true if this object can be tagged and cached on client side.
     */
    public function canBeEtagged(): bool
    {
        return (bool) $this->getTimestampField();
    }

    /**
     * Cached tag value.
     */
    private ?string $tag = null;

    /**
     * Return collection etag.
     */
    public function getEtag(
        string $visitor_identifier,
        bool $use_cache = true
    ): string
    {
        $timestamp_field = $this->getTimestampField();

        if ($timestamp_field && ($this->tag === null || !$use_cache)) {
            $this->tag = $this->prepareTagFromBits(
                $this->getAdditionalIdentifier(),
                $visitor_identifier,
                $this->getTimestampHash($timestamp_field),
            );
        }

        return $this->tag;
    }

    protected function getAdditionalIdentifier(): string
    {
        return 'na';
    }

    /**
     * Cached time stamp field name.
     */
    private string|false $timestamp_field = '';

    public function getTimestampField(): string|false
    {
        if ($this->timestamp_field === '') {
            $fields = $this->pool->getTypeFields($this->getRegisteredType());

            if (in_array('updated_at', $fields)) {
                $this->timestamp_field = 'updated_at';
            } elseif (in_array('created_at', $fields)) {
                $this->timestamp_field = 'created_at';
            } else {
                $this->timestamp_field = false;
            }
        }

        return $this->timestamp_field;
    }

    /**
     * Return timestamp hash.
     */
    public function getTimestampHash(string $timestamp_field): string
    {
        if (!$this->isReady()) {
            throw new LogicException('Collection is not ready');
        }

        $table_name = $this->getTableName();
        $conditions = $this->getConditions() ? " WHERE {$this->getConditions()}" : '';

        if ($this->count() > 0) {
            if ($join_expression = $this->getJoinExpression()) {
                return sha1(
                    $this->connection->executeFirstCell(
                        "SELECT GROUP_CONCAT($table_name.$timestamp_field ORDER BY $table_name.id SEPARATOR ',') AS 'timestamp_hash' FROM $table_name $join_expression $conditions",
                    ),
                );
            }

            return sha1(
                $this->connection->executeFirstCell(
                    "SELECT GROUP_CONCAT($table_name.$timestamp_field ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM $table_name $conditions"
                ),
            );
        }

        return sha1($this::class);
    }

    // ---------------------------------------------------
    //  Model interaction
    // ---------------------------------------------------

    /**
     * Run the query and return DB result.
     *
     * @return ResultInterface|EntityInterface[]
     */
    public function execute(): ?iterable
    {
        if (!$this->isReady()) {
            throw new LogicException('Collection is not ready');
        }

        if (is_callable($this->pre_execute_callback)) {
            $ids = $this->executeIds();
            $ids_count = count($ids);

            if ($ids_count === 0) {
                return null;
            }

            call_user_func($this->pre_execute_callback, $ids);

            if ($ids_count > 1000) {
                $sql = $this->getSelectSql(); // Don't escape more than 1000 ID-s using DB::escape(), let MySQL do the dirty work instead of PHP
            } else {
                $escaped_ids = $this->connection->escapeValue($ids);

                $sql = sprintf(
                    "SELECT * FROM %s WHERE `id` IN (%s) ORDER BY FIELD (`id`, %s)",
                    $this->getTableName(),
                    $escaped_ids,
                    $escaped_ids,
                );
            }

            return $this->pool->findBySql($this->getType(), $sql);
        }

        return $this->pool->findBySql($this->getType(), $this->getSelectSql());
    }

    private ?array $ids = null;

    /**
     * Return ID-s of matching records.
     */
    public function executeIds(): array
    {
        if (!$this->isReady()) {
            throw new LogicException('Collection is not ready');
        }

        if ($this->ids === null) {
            $this->ids = $this->connection->executeFirstColumn($this->getSelectSql(false));

            if (empty($this->ids)) {
                $this->ids = [];
            }
        }

        return $this->ids;
    }

    /**
     * Return number of items that will be displayed on the current page of paginated collection (or total, if collection is not paginated).
     */
    public function countIds(): int
    {
        return count($this->executeIds());
    }

    /**
     * @param  bool   $all_fields
     * @return string
     */
    private function getSelectSql($all_fields = true)
    {
        $offset = $this->getCurrentPage() !== null ? ($this->getCurrentPage() - 1) * $this->getItemsPerPage() : null;
        $limit = $this->getItemsPerPage();

        $fields = $all_fields ? '*' : '`id`';
        $table_name = $this->connection->escapeTableName($this->getTableName());
        $conditions = $this->getConditions() ? "WHERE {$this->getConditions()}" : '';

        if ($order_by = $this->getOrderBy()) {
            $order_by = "ORDER BY $this->order_by";
        } else {
            $order_by = '';
        }

        $limit = is_int($offset) && $limit ? "LIMIT $offset, $limit" : '';

        if ($join_expression = $this->getJoinExpression()) {
            return "SELECT $table_name.$fields FROM $table_name $join_expression $conditions $order_by $limit";
        } else {
            return "SELECT $fields FROM $table_name $conditions $order_by $limit";
        }
    }

    /**
     * Return number of records that match conditions set by the collection.
     */
    public function count(): int
    {
        if (!$this->isReady()) {
            throw new LogicException('Collection is not ready');
        }

        $table_name = $this->connection->escapeTableName($this->getTableName());
        $conditions = $this->getConditions() ? " WHERE {$this->getConditions()}" : '';

        if ($join_expression = $this->getJoinExpression()) {
            return (int) $this->connection->executeFirstCell("SELECT COUNT($table_name.`id`) FROM $table_name $join_expression $conditions");
        } else {
            return $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM $table_name $conditions");
        }
    }

    private ?string $table_name = null;

    /**
     * Return model table name.
     */
    public function getTableName(): string
    {
        if (empty($this->table_name)) {
            $this->table_name = $this->pool->getTypeTable($this->getRegisteredType());
        }

        return $this->table_name;
    }

    /**
     * Cached order by value.
     *
     * @var string|null
     */
    private $order_by = false;

    /**
     * Return order by.
     *
     * @return string|null
     */
    public function getOrderBy()
    {
        if ($this->order_by === false) {
            $this->order_by = $this->pool->getEscapedTypeOrderBy($this->getRegisteredType());
        }

        return $this->order_by;
    }

    /**
     * Set how system should order records in this collection.
     *
     * @param  string $value
     * @return $this
     */
    public function &orderBy($value)
    {
        if ($value === null || $value) {
            $this->order_by = $value;
        } else {
            throw new InvalidArgumentException('$value can be NULL or a valid order by value');
        }

        return $this;
    }

    /**
     * Collection conditions.
     *
     * @var array
     */
    private $conditions = [];

    /**
     * Cached conditions as string.
     *
     * Call to where() resets this value to false, and getConditions() rebuilds it if it FALSE on request.
     *
     * @var string|bool
     */
    private $conditions_as_string = false;

    /**
     * Return conditions.
     *
     * @return string|null
     */
    public function getConditions()
    {
        if ($this->conditions_as_string === false) {
            switch (count($this->conditions)) {
                case 0:
                    $this->conditions_as_string = '';
                    break;
                case 1:
                    $this->conditions_as_string = $this->conditions[0];
                    break;
                default:
                    $this->conditions_as_string = implode(' AND ', array_map(function ($condition) {
                        return "($condition)";
                    }, $this->conditions));
            }
        }

        return $this->conditions_as_string;
    }

    /**
     * Set collection conditions.
     *
     * @param  string|array $pattern
     * @param  array        $arguments
     * @return $this
     */
    public function &where($pattern, ...$arguments)
    {
        if (empty($pattern)) {
            throw new InvalidArgumentException('Pattern argument is required');
        }

        if (is_string($pattern)) {
            $this->conditions[] = $this->connection->prepareConditions(array_merge([$pattern], $arguments));
        } elseif (is_array($pattern)) {
            if (!empty($arguments)) {
                throw new LogicException('When pattern is an array, no extra arguments are allowed');
            }

            $this->conditions[] = $this->connection->prepareConditions($pattern);
        } else {
            throw new InvalidArgumentException('Pattern can be string or an array');
        }

        // Force rebuild of conditions as string on next getConditions() call
        $this->conditions_as_string = false;

        return $this;
    }

    // ---------------------------------------------------
    //  Joining support
    // ---------------------------------------------------

    /**
     * Name of the join table.
     *
     * @var string
     */
    private $join_table;

    /**
     * Return join table name.
     *
     * @return string
     */
    public function getJoinTable()
    {
        return $this->join_table;
    }

    /**
     * Set join table name.
     *
     * If $join_field is null, join field will be based on model name. There are two ways to specify it:
     *
     * 1. As string, where value is for target field, and it will map with ID column of the source table,
     * 2. As array, where first element is ID in the source table and second element is field in target table
     *
     * @param  string            $table_name
     * @param  array|string|null $join_field
     * @return $this
     */
    public function &setJoinTable($table_name, $join_field = null)
    {
        $this->join_table = $table_name;

        if (empty($this->target_join_field)) {
            if (is_string($join_field) && $join_field) {
                $this->setTargetJoinField($join_field);
            } elseif (is_array($join_field)) {
                if (count($join_field) == 2 && !empty($join_field[0]) && !empty($join_field[1])) {
                    $this->setSourceJoinField($join_field[0]);
                    $this->setTargetJoinField($join_field[1]);
                } else {
                    throw new InvalidArgumentException('Join field should be an array with two elements');
                }
            } else {
                $registered_type = $this->getRegisteredType();

                $inflector = InflectorFactory::create()->build();

                if (($pos = strrpos($registered_type, '\\')) !== false) {
                    $this->target_join_field = sprintf(
                        '%s_id',
                        $inflector->singularize($inflector->tableize(substr($registered_type, $pos + 1)))
                    );
                } else {
                    $this->target_join_field = sprintf(
                        '%s_id',
                        $inflector->singularize($inflector->tableize($registered_type))
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Join field in source table.
     *
     * @var string
     */
    private $source_join_field = 'id';

    /**
     * @return string
     */
    public function getSourceJoinField()
    {
        return $this->source_join_field;
    }

    /**
     * @param  string $value
     * @return $this
     */
    public function &setSourceJoinField($value)
    {
        $this->source_join_field = $value;

        return $this;
    }

    /**
     * Join with field name.
     *
     * @var string
     */
    private $target_join_field;

    /**
     * Return join field name.
     *
     * @return string
     */
    public function getTargetJoinField()
    {
        return $this->target_join_field;
    }

    /**
     * Set join field name.
     *
     * @param  string $value
     * @return $this
     */
    public function &setTargetJoinField($value)
    {
        $this->target_join_field = $value;

        return $this;
    }

    /**
     * Return join expression.
     *
     * @return string|null
     */
    private function getJoinExpression()
    {
        if ($this->join_table && $this->target_join_field) {
            return "LEFT JOIN `$this->join_table` ON `" . $this->getTableName() . "`.`$this->source_join_field` = `$this->join_table`.`$this->target_join_field`";
        }

        return null;
    }

    /**
     * @var callable
     */
    private $pre_execute_callback;

    /**
     * Set a callback that will be triggered prior to collection execution.
     *
     * @param callable $callback
     */
    public function setPreExecuteCallback(callable $callback)
    {
        $this->pre_execute_callback = $callback;
    }
}
