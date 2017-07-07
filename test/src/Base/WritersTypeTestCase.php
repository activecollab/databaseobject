<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Base;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test\Base
 */
abstract class WritersTypeTestCase extends TestCase
{
    /**
     * Set up test environment.
     */
    public function setUp()
    {
        parent::setUp();

        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        $create_table = $this->connection->execute('CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci,
            `birthday` date NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

        $this->assertTrue($create_table);

        $this->connection->execute(
            'INSERT INTO `writers` (`name`, `birthday`)
                VALUES (?, ?), (?, ?), (?, ?)',
            'Leo Tolstoy',
            new DateValue('1828-09-09'),
            'Alexander Pushkin',
            new DateValue('1799-06-06'),
            'Fyodor Dostoyevsky',
            new DateValue('1821-11-11')
        );

        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
    }

    /**
     * Tear down test environment.
     */
    public function tearDown()
    {
        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        parent::tearDown();
    }
}
