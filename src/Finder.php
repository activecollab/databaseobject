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
use Doctrine\Inflector\InflectorFactory;
use Psr\Log\LoggerInterface;

class Finder implements FinderInterface, ContainerAccessInterface
{
    use ContainerAccessInterfaceImplementation;

    private ConnectionInterface $connection;
    private PoolInterface $pool;
    private LoggerInterface $logger;
    private string $type;
    private array $where = [];
    private string $order_by;
    private ?int $offset = null;
    private ?int $limit = null;
    private ?string $join = null;

    public function __construct(
        ConnectionInterface $connection,
        PoolInterface $pool,
        LoggerInterface $logger,
        string $type
    )
    {
        $this->connection = $connection;
        $this->pool = $pool;
        $this->logger = $logger;
        $this->type = $type;
        $this->order_by = $this->pool->getEscapedTypeOrderBy($type);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSelectSql(): string
    {
        return $this->getSelectFieldsSql($this->pool->getTypeFieldsReadStatement($this->type));
    }

    public function getSelectIdsSql(): string
    {
        return $this->getSelectFieldsSql($this->getEscapedTableName() . '.`id`');
    }

    public function __toString(): string
    {
        return $this->getSelectSql();
    }

    // ---------------------------------------------------
    //  Configuration
    // ---------------------------------------------------

    /**
     * Set finder  .
     *
     * @param  string|array          $pattern
     * @param  mixed                 ...$arguments
     * @return FinderInterface|$this
     */
    public function where(string $pattern, ...$arguments): FinderInterface
    {
        $conditions_to_prepare = [$pattern];

        if (!empty($arguments)) {
            $conditions_to_prepare = array_merge($conditions_to_prepare, $arguments);
        }

        $this->where[] = $this->connection->prepareConditions($conditions_to_prepare);

        return $this;
    }

    /**
     * Return where part of the query.
     */
    private function getWhere(): string
    {
        switch (count($this->where)) {
            case 0:
                return '';
            case 1:
                return $this->where[0];
            default:
                return implode(' AND ', array_map(function ($condition) {
                    return "($condition)";
                }, $this->where));
        }
    }

    public function orderBy(string $order_by): FinderInterface
    {
        $this->order_by = $order_by;

        return $this;
    }

    public function limit(int $offset, int $limit): FinderInterface
    {
        $this->offset = $offset;
        $this->limit = $limit;

        return $this;
    }

    public function join(string $type, string $field_name = null): FinderInterface
    {
        return $this->joinTable($this->pool->getTypeTable($type), $field_name);
    }

    public function joinTable(string $table_name, string $field_name = null): FinderInterface
    {
        $join_table = $this->connection->escapeTableName($table_name);
        $join_field = $this->connection->escapeFieldName(
            $field_name ? $field_name : $this->getJoinFieldNameFromType()
        );

        $this->join = "LEFT JOIN $join_table ON {$this->getEscapedTableName()}.`id` = $join_table.$join_field";

        return $this;
    }

    private function getJoinFieldNameFromType(): string
    {
        if (($pos = strrpos($this->getType(), '\\')) === false) {
            $type = $this->getType();
        } else {
            $type = substr($this->getType(), $pos + 1);
        }

        return sprintf('%s_id', InflectorFactory::create()->build()->tableize($type));
    }

    // ---------------------------------------------------
    //  Execution
    // ---------------------------------------------------

    public function count(): int
    {
        $table_name = $this->getEscapedTableName();

        $sql = sprintf("SELECT COUNT(%s.`id`) AS 'row_count' FROM %s",
            $table_name,
            $table_name
        );

        if ($this->join) {
            $sql .= " $this->join";
        }

        if ($where = $this->getWhere()) {
            $sql .= " WHERE $where";
        }

        return $this->connection->executeFirstCell($sql);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function existsOne(): bool
    {
        return $this->count() === 1;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): ?iterable
    {
        return $this->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function first(): ?EntityInterface
    {
        if ($this->offset === null) {
            $this->offset = 0;
        }

        $this->limit = 1;

        if ($result = $this->execute()) {
            return $result[0];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function ids(): ?iterable
    {
        $ids = $this->connection->executeFirstColumn($this->getSelectIdsSql());

        return empty($ids) ? [] : $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $select_sql = $this->getSelectSql();

        if ($this->loadByTypeField()) {
            $return_by = ConnectionInterface::RETURN_OBJECT_BY_FIELD;
            $return_by_value = 'type';
        } else {
            $return_by = ConnectionInterface::RETURN_OBJECT_BY_CLASS;
            $return_by_value = $this->type;
        }

        if ($this->hasContainer()) {
            return $this->connection->advancedExecute(
                $select_sql,
                null,
                ConnectionInterface::LOAD_ALL_ROWS,
                $return_by,
                $return_by_value,
                [
                    &$this->connection,
                    &$this->pool,
                    &$this->logger,
                ],
                $this->getContainer()
            );
        } else {
            return $this->connection->advancedExecute(
                $select_sql,
                null,
                ConnectionInterface::LOAD_ALL_ROWS,
                $return_by,
                $return_by_value,
                [
                    &$this->connection,
                    &$this->pool,
                    &$this->logger,
                ]
            );
        }
    }

    // ---------------------------------------------------
    //  Utilities
    // ---------------------------------------------------

    private ?bool $load_by_type_field = null;

    private function loadByTypeField(): bool
    {
        if ($this->load_by_type_field === null) {
            $this->load_by_type_field = in_array('type', $this->pool->getTypeFields($this->type));
        }

        return $this->load_by_type_field;
    }

    private function getSelectFieldsSql(string $fields_read_statement): string
    {
        $result = sprintf(
            'SELECT %s FROM %s',
            $fields_read_statement,
            $this->getEscapedTableName()
        );

        if ($this->join) {
            $result .= " $this->join";
        }

        if ($where = $this->getWhere()) {
            $result .= " WHERE $where";
        }

        if ($this->order_by) {
            $result .= " ORDER BY $this->order_by";
        }

        if ($this->offset !== null && $this->limit !== null) {
            $result .= " LIMIT $this->offset, $this->limit";
        }

        return $result;
    }

    private ?string $escaped_table_name = null;

    private function getEscapedTableName(): string
    {
        if (empty($this->escaped_table_name)) {
            $this->escaped_table_name = $this->pool->getTypeTable($this->type, true);
        }

        return $this->escaped_table_name;
    }
}
