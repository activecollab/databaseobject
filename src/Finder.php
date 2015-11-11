<?php
namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Result\Result;
use InvalidArgumentException;

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
     * @var ConnectionInterface
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
     * @param PoolInterface       $pool
     * @param ConnectionInterface $connection
     * @param string              $type
     */
    public function __construct(PoolInterface $pool, ConnectionInterface $connection, $type)
    {
        $this->pool = $pool;
        $this->connection = $connection;
        $this->type = $type;
        $this->order_by = $this->pool->getEscapedTypeOrderBy($type);
    }

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

        $this->conditions = $this->connection->prepareConditions($conditions_to_prepare);

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
     * Return number of records that match the given criteria
     *
     * @return integer
     */
    public function count()
    {
        $sql = "SELECT COUNT(`id`) AS 'row_count' FROM " . $this->getEscapedTableName();

        if ($this->conditions) {
            $sql .= " WHERE $this->conditions";
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
        return $this->connection->executeFirstColumn($this->getSelectIdsSql());
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

        return $this->connection->advancedExecute($select_sql, null, ConnectionInterface::LOAD_ALL_ROWS, $return_by, $return_by_value, [&$this->pool, &$this->connection]);
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
        return $this->getSelectFieldsSql('`id`');
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
