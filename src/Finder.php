<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\ContainerAccess\ContainerAccessInterface;
use ActiveCollab\ContainerAccess\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use Doctrine\Common\Inflector\Inflector;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Finder implements FinderInterface, ContainerAccessInterface
{
    use ContainerAccessInterfaceImplementation;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string[]
     */
    private $where = [];

    /**
     * @var string
     */
    private $order_by;

    /**
     * @var int|null
     */
    private $offset;

    /**
     * @var int|null
     */
    private $limit;

    /**
     * @var string
     */
    private $join;

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
        return $this->getSelectFieldsSql($this->pool->getEscapedTypeFields($this->type));
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
    public function &where($pattern, ...$arguments): FinderInterface
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Conditions pattern needs to be string');
        }

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

    /**
     * @param  string                $order_by
     * @return FinderInterface|$this
     */
    public function &orderBy($order_by): FinderInterface
    {
        $this->order_by = $order_by;

        return $this;
    }

    /**
     * @param  int                   $offset
     * @param  int                   $limit
     * @return FinderInterface|$this
     */
    public function &limit($offset, $limit): FinderInterface
    {
        $this->offset = $offset;
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param  string                $type
     * @param  string                $field_name
     * @return FinderInterface|$this
     */
    public function &join($type, $field_name = null): FinderInterface
    {
        return $this->joinTable($this->pool->getTypeTable($type), $field_name);
    }

    /**
     * @param  string                $table_name
     * @param  string                $field_name
     * @return FinderInterface|$this
     */
    public function &joinTable($table_name, $field_name = null): FinderInterface
    {
        $join_table = $this->connection->escapeTableName($table_name);
        $join_field = $this->connection->escapeFieldName($field_name ? $field_name : $this->getJoinFieldNameFromType());

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

        return Inflector::tableize($type) . '_id';
    }

    // ---------------------------------------------------
    //  Execution
    // ---------------------------------------------------

    public function count(): int
    {
        $table_name = $this->getEscapedTableName();

        $sql = "SELECT COUNT($table_name.`id`) AS 'row_count' FROM $table_name";

        if ($this->join) {
            $sql .= " $this->join";
        }

        if ($where = $this->getWhere()) {
            $sql .= " WHERE $where";
        }

        return $this->connection->executeFirstCell($sql);
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

    private $load_by_type_field;

    private function loadByTypeField(): string
    {
        if ($this->load_by_type_field === null) {
            $this->load_by_type_field = in_array('type', $this->pool->getTypeFields($this->type));
        }

        return $this->load_by_type_field;
    }

    private function getSelectFieldsSql(string $escaped_field_names): string
    {
        $result = "SELECT $escaped_field_names FROM " . $this->getEscapedTableName();

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

    private $escaped_table_name;

    private function getEscapedTableName(): string
    {
        if (empty($this->escaped_table_name)) {
            $this->escaped_table_name = $this->pool->getTypeTable($this->type, true);
        }

        return $this->escaped_table_name;
    }
}
