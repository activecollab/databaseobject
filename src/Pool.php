<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Connection;

use Doctrine\Common\Inflector\Inflector;

/**
 * @package ActiveCollab\DatabaseObject
 */
class Pool
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function persist(Object $object)
    {

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
        return (boolean) $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM ' . $this->connection->escapeTableName($this->getTableByType($type)) . ' WHERE `id` = ?', $id);
    }

    /**
     * Return table name by type
     *
     * @TODO This needs to be better
     *
     * @param  string $type
     * @return string
     */
    public function getTableByType($type)
    {
        $bits = explode('\\', $type);

        return Inflector::tableize(Inflector::pluralize(array_pop($bits)));
    }
}