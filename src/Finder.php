<?php
namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Result\Result;
use ActiveCollab\DatabaseObject\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;
use ActiveCollab\DatabaseObject\ObjectConstructorArgsInterface\Implementation as ObjectConstructorArgsInterfaceImplementation;
use Doctrine\Common\Inflector\Inflector;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Finder implements FinderInterface, ContainerAccessInterface
{
    use ObjectConstructorArgsInterfaceImplementation, ContainerAccessInterfaceImplementation;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var LoggerInterface
     */
    private $log;

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
     * @var integer|null
     */
    private $offset;

    /**
     * @var integer|null
     */
    private $limit;

    /**
     * @var string
     */
    private $join;
    
    /**
     * @param PoolInterface        $pool
     * @param ConnectionInterface  $connection
     * @param LoggerInterface|null $log
     * @param string               $type
     */
    public function __construct(ConnectionInterface $connection, PoolInterface $pool, LoggerInterface &$log = null, $type)
    {
        $this->connection = $connection;
        $this->pool = $pool;
        $this->log = $log;
        $this->type = $type;
        $this->order_by = $this->pool->getEscapedTypeOrderBy($type);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    // ---------------------------------------------------
    //  Configuration
    // ---------------------------------------------------

    /**
     * Set finder conditions
     *
     * @param  string $pattern
     * @param  mixed  ...$arguments
     * @return $this
     */
    public function &where($pattern, ...$arguments)
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
     * Return where part of the query
     */
    public function getWhere()
    {
        switch (count($this->where)) {
            case 0:
                return '';
            case 1:
                return $this->where[0];
            default:
                return implode(' AND ', array_map(function($condition) {
                    return "($condition)";
                }, $this->where));
        }
    }

    /**
     * @param  string $order_by
     * @return $this
     */
    public function &orderBy($order_by)
    {
        $this->order_by = $order_by;

        return $this;
    }

    /**
     * @param  integer $offset
     * @param  integer $limit
     * @return $this
     */
    public function &limit($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param  string $type
     * @param  string $field_name
     * @return $this
     */
    public function &join($type, $field_name = null)
    {
        return $this->joinTable($this->pool->getTypeTable($type), $field_name);
    }

    /**
     * @param  string $table_name
     * @param  string $field_name
     * @return $this
     */
    public function &joinTable($table_name, $field_name = null)
    {
        $join_table = $this->connection->escapeTableName($table_name);
        $join_field = $this->connection->escapeFieldname($field_name ? $field_name : $this->getJoinFieldNameFromType());

        $this->join = "LEFT JOIN $join_table ON {$this->getEscapedTableName()}.`id` = $join_table.$join_field";

        return $this;
    }

    /**
     * @return string
     */
    private function getJoinFieldNameFromType()
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

    /**
     * Return number of records that match the given criteria
     *
     * @return integer
     */
    public function count()
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
     * Return all records that match the given criteria
     *
     * @return Result|Object[]|null
     */
    public function all()
    {
        return $this->execute();
    }

    /**
     * Return first record that matches the given criteria
     *
     * @return Object|null
     */
    public function first()
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
     * Return array of ID-s that match the given criteria
     *
     * @return integer[]
     */
    public function ids()
    {
        $ids = $this->connection->executeFirstColumn($this->getSelectIdsSql());

        return empty($ids) ? [] : $ids;
    }

    /**
     * Prepare SQL and load one or more records
     *
     * @return mixed
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

        return $this->connection->advancedExecute($select_sql, null, ConnectionInterface::LOAD_ALL_ROWS, $return_by, $return_by_value, $this->getObjectConstructorArgs());
    }

    // ---------------------------------------------------
    //  Utilities
    // ---------------------------------------------------

    /**
     * @var boolean
     */
    private $load_by_type_field;

    /**
     * Check if we should load by type field or by type class
     *
     * @return bool
     */
    private function loadByTypeField()
    {
        if ($this->load_by_type_field === null) {
            $this->load_by_type_field = in_array('type', $this->pool->getTypeFields($this->type));
        }

        return $this->load_by_type_field;
    }

    /**
     * Prepare select one or more rows query
     *
     * @return string
     */
    private function getSelectSql()
    {
        return $this->getSelectFieldsSql($this->pool->getEscapedTypeFields($this->type));
    }

    /**
     * Return select ID-s SQL
     *
     * @return string
     */
    private function getSelectIdsSql()
    {
        return $this->getSelectFieldsSql($this->getEscapedTableName() . '.`id`');
    }

    /**
     * Construct SELECT query for the given fields based on set criteria
     *
     * @param  string $escaped_field_names
     * @return string
     */
    private function getSelectFieldsSql($escaped_field_names)
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

    /**
     * @var string
     */
    private $escaped_table_name;

    /**
     * @return string
     */
    private function getEscapedTableName()
    {
        if (empty($this->escaped_table_name)) {
            $this->escaped_table_name = $this->pool->getTypeTable($this->type, true);
        }

        return $this->escaped_table_name;
    }
}
