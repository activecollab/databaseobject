<?php
namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseConnection\Result\Result;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Finder
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $conditions;

    /**
     * @var string
     */
    private $order_by;

    /**
     * @var integer|null
     */
    private $offset, $limit;
    
    /**
     * @param Pool       $pool
     * @param Connection $connection
     * @param            $type
     */
    public function __construct(Pool $pool, Connection $connection, $type)
    {
        $this->pool = $pool;
        $this->connection = $connection;
        $this->type = $type;
        $this->order_by = $this->pool->getEscapedTypeOrderBy($type);
    }

    /**
     * @return $this
     */
    public function &where()
    {
        $this->conditions = $this->connection->prepareConditions(func_get_args());

        return $this;
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
     * Prepare SQL and load one or more records
     *
     * @return mixed
     */
    public function execute()
    {
        $select_sql = $this->getSelectSql();

        if ($this->loadByTypeField()) {
            $return_by = Connection::RETURN_OBJECT_BY_FIELD;
            $return_by_value = 'type';
        } else {
            $return_by = Connection::RETURN_OBJECT_BY_CLASS;
            $return_by_value = $this->type;
        }

        return $this->connection->advancedExecute($select_sql, null, Connection::LOAD_ALL_ROWS, $return_by, $return_by_value, [&$this->pool, &$this->connection]);
    }

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
    public function getSelectSql()
    {
        $result = "SELECT " . $this->pool->getEscapedTypeFields($this->type) . " FROM " . $this->pool->getTypeTable($this->type, true);

        if ($this->conditions) {
            $result .= " WHERE $this->conditions";
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}