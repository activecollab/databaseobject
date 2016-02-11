<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Collection;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Collection;
use ActiveCollab\DatabaseObject\ObjectInterface;
use Doctrine\Common\Inflector\Inflector;
use InvalidArgumentException;
use LogicException;

/**
 * @package ActiveCollab\DatabaseObject\Collection
 */
abstract class Type extends Collection
{
    /**
     * @var string
     */
    private $registered_type;

    /**
     * Return type that this collection works with.
     *
     * @return string
     */
    abstract public function getType();

    /**
     * Return registered type.
     *
     * @return string
     */
    protected function getRegisteredType()
    {
        if (empty($this->registered_type)) {
            $this->registered_type = $this->pool->getRegisteredType($this->getType());

            if (empty($this->registered_type)) {
                throw new InvalidArgumentException("Type '" . $this->getType() . "' is not registered");
            }
        }

        return $this->registered_type;
    }

    // ---------------------------------------------------
    //  Etag
    // ---------------------------------------------------

    /**
     * Return true if this object can be tagged and cached on client side.
     *
     * @return bool|null
     */
    public function canBeEtagged()
    {
        return (boolean) $this->getTimestampField();
    }

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    /**
     * Return collection etag.
     *
     * @param  string $visitor_identifier
     * @param  bool   $use_cache
     * @return string
     */
    public function getEtag($visitor_identifier, $use_cache = true)
    {
        $timestamp_field = $this->getTimestampField();

        if ($timestamp_field && ($this->tag === false || !$use_cache)) {
            $this->tag = $this->prepareTagFromBits($this->getAdditionalIdentifier(), $visitor_identifier, $this->getTimestampHash($timestamp_field));
        }

        return $this->tag;
    }

    /**
     * @return string
     */
    protected function getAdditionalIdentifier()
    {
        return 'na';
    }

    /**
     * Cached time stamp field name.
     *
     * @var string|bool
     */
    private $timestamp_field = null;

    /**
     * Return timestamp field name.
     *
     * @return string|bool
     */
    public function getTimestampField()
    {
        if ($this->timestamp_field === null) {
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
     *
     * @param  string $timestamp_field
     * @return string
     */
    public function getTimestampHash($timestamp_field)
    {
        if (!$this->isReady()) {
            throw new LogicException('Collection is not ready');
        }

        $table_name = $this->getTableName();
        $conditions = $this->conditions ? " WHERE $this->conditions" : '';

        if ($this->count() > 0) {
            if ($join_expression = $this->getJoinExpression()) {
                return sha1($this->connection->executeFirstCell("SELECT GROUP_CONCAT($table_name.$timestamp_field ORDER BY $table_name.id SEPARATOR ',') AS 'timestamp_hash' FROM $table_name $join_expression $conditions"));
            } else {
                return sha1($this->connection->executeFirstCell("SELECT GROUP_CONCAT($table_name.$timestamp_field ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM $table_name $conditions"));
            }
        }

        return sha1(get_class($this));
    }

    // ---------------------------------------------------
    //  Model interaction
    // ---------------------------------------------------

    /**
     * Run the query and return DB result.
     *
     * @return ResultInterface|ObjectInterface[]
     */
    public function execute()
    {
        if (!$this->isReady()) {
            throw new LogicException('Collection is not ready');
        }

        if (is_callable($this->pre_execute_callback)) {
            $ids = $this->executeIds();

            if ($ids_count = count($ids)) {
                call_user_func($this->pre_execute_callback, $ids);

                if ($ids_count > 1000) {
                    $sql = $this->getSelectSql(); // Don't escape more than 1000 ID-s using DB::escape(), let MySQL do the dirty work instead of PHP
                } else {
                    $escaped_ids = $this->connection->escapeValue($ids);

                    $sql = 'SELECT * FROM ' . $this->getTableName() . " WHERE id IN ($escaped_ids) ORDER BY FIELD (id, $escaped_ids)";
                }

                return $this->pool->findBySql($this->getType(), $sql);
            }

            return null;
        } else {
            return $this->pool->findBySql($this->getType(), $this->getSelectSql());
        }
    }

    /**
     * @var int[]
     */
    private $ids = false;

    /**
     * Return ID-s of matching records.
     *
     * @return array
     */
    public function executeIds()
    {
        if (!$this->isReady()) {
            throw new LogicException('Collection is not ready');
        }

        if ($this->ids === false) {
            $this->ids = $this->connection->executeFirstColumn($this->getSelectSql(false));

            if (empty($this->ids)) {
                $this->ids = [];
            }
        }

        return $this->ids;
    }

    /**
     * Return number of items that will be displayed on the current page of paginated collection (or total, if collection is not paginated).
     *
     * @return int
     */
    public function countIds()
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
        $conditions = $this->conditions ? "WHERE $this->conditions" : '';

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
     *
     * @return int
     */
    public function count()
    {
        if (!$this->isReady()) {
            throw new LogicException('Collection is not ready');
        }

        $table_name = $this->connection->escapeTableName($this->getTableName());
        $conditions = $this->conditions ? " WHERE $this->conditions" : '';

        if ($join_expression = $this->getJoinExpression()) {
            return (integer) $this->connection->executeFirstCell("SELECT COUNT($table_name.`id`) FROM $table_name $join_expression $conditions");
        } else {
            return $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM $table_name $conditions");
        }
    }

    /**
     * Return model table name.
     *
     * @var string
     */
    private $table_name;

    /**
     * Return model table name.
     *
     * @return mixed
     */
    public function getTableName()
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
     * @var string
     */
    private $conditions;

    /**
     * Return conditions.
     *
     * @return string|null
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Set collection conditions.
     *
     * @param  mixed ...$arguments
     * @return $this
     */
    public function &where()
    {
        $number_of_arguments = func_num_args();

        if ($number_of_arguments === 1) {
            $this->conditions = $this->connection->prepareConditions(func_get_arg(0));
        } elseif ($number_of_arguments > 1) {
            $this->conditions = $this->connection->prepareConditions(func_get_args());
        } else {
            throw new InvalidArgumentException('At least one argument expected');
        }

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
     * 1. As string, where value is for target field and it will map with ID column of the source table,
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

                if (($pos = strrpos($registered_type, '\\')) !== false) {
                    $this->target_join_field = Inflector::singularize(Inflector::tableize(substr($registered_type, $pos + 1))) . '_id';
                } else {
                    $this->target_join_field = Inflector::singularize(Inflector::tableize($registered_type)) . '_id';
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
