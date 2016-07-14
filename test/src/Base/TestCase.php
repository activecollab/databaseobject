<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Base;

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseObject\Pool;
use mysqli;

/**
 * @package ActiveCollab\DatabaseObject\Test\Base
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var mysqli
     */
    protected $link;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * Set up test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->link = new \MySQLi('localhost', 'root', '', 'activecollab_database_object_test');

        if ($this->link->connect_error) {
            throw new \RuntimeException('Failed to connect to database. MySQL said: ' . $this->link->connect_error);
        }

        $this->connection = new Connection($this->link);
        $this->pool = new Pool($this->connection);
    }

    /**
     * Tear down test environment.
     */
    public function tearDown()
    {
        $this->connection = null;
        $this->pool = null;
        $this->link->close();

        parent::tearDown();
    }
}
