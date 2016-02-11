<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseConnection\Result\Result;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class FindJoinTest extends WritersTypeTestCase
{
    /**
     * Set up test environment.
     */
    public function setUp()
    {
        parent::setUp();

        if ($this->connection->tableExists('writer_groups')) {
            $this->connection->dropTable('writer_groups');
        }

        $create_table = $this->connection->execute("CREATE TABLE `writer_groups` (
            `writer_id` int(11) NOT NULL DEFAULT '0',
            `group_id` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`writer_id`, `group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writer_groups` (`writer_id`, `group_id`) VALUES (?, ?), (?, ?), (?, ?)', 1, 1, 2, 1, 3, 2);

        $this->assertEquals(2, $this->connection->count('writer_groups', ['group_id = ?', 1], '*'));
        $this->assertEquals(1, $this->connection->count('writer_groups', ['group_id = ?', 2], '*'));
    }

    /**
     * Tear down test environment.
     */
    public function tearDown()
    {
        if ($this->connection->tableExists('writer_groups')) {
            $this->connection->dropTable('writer_groups');
        }

        parent::tearDown();
    }

    /**
     * Test count all.
     */
    public function testCount()
    {
        $this->assertEquals(3, $this->pool->find(Writer::class)->count());
        $this->assertEquals(2, $this->pool->find(Writer::class)->joinTable('writer_groups')->where('`writer_groups`.`group_id` = ?', 1)->count());
        $this->assertEquals(1, $this->pool->find(Writer::class)->joinTable('writer_groups')->where('`writer_groups`.`group_id` = ?', 2)->count());
    }

    /**
     * Test find all writers from the database.
     */
    public function testFindAll()
    {
        /** @var Result $result */
        $result = $this->pool->find(Writer::class)->all();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount(3, $result);

        /** @var Result $result */
        $result = $this->pool->find(Writer::class)->joinTable('writer_groups')->where('`writer_groups`.`group_id` = ?', 1)->all();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount(2, $result);

        /** @var Result $result */
        $result = $this->pool->find(Writer::class)->joinTable('writer_groups')->where('`writer_groups`.`group_id` = ?', 2)->all();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount(1, $result);
    }

    /**
     * Test find first record.
     */
    public function testFindFirst()
    {
        /* @var Writer $should_be_leo */
        $should_be_tolstoy = $this->pool->find(Writer::class)->orderBy('`id`')->first();

        $this->assertInstanceOf(Writer::class, $should_be_tolstoy);
        $this->assertTrue($should_be_tolstoy->isLoaded());
        $this->assertEquals('Leo Tolstoy', $should_be_tolstoy->getName());

        /** @var Writer $should_be_tolstoy */
        $should_be_tolstoy = $this->pool->find(Writer::class)->orderBy('`id`')->joinTable('writer_groups')->where('`writer_groups`.`group_id` = ?', 1)->first();

        $this->assertInstanceOf(Writer::class, $should_be_tolstoy);
        $this->assertTrue($should_be_tolstoy->isLoaded());
        $this->assertEquals('Leo Tolstoy', $should_be_tolstoy->getName());

        /** @var Writer $should_be_dostoyevsky */
        $should_be_dostoyevsky = $this->pool->find(Writer::class)->orderBy('`id`')->joinTable('writer_groups')->where('`writer_groups`.`group_id` = ?', 2)->first();

        $this->assertInstanceOf(Writer::class, $should_be_dostoyevsky);
        $this->assertTrue($should_be_dostoyevsky->isLoaded());
        $this->assertEquals('Fyodor Dostoyevsky', $should_be_dostoyevsky->getName());
    }

    /**
     * Test find all ID-s.
     */
    public function testFindAllIds()
    {
        $ids = $this->pool->find(Writer::class)->orderBy('`id`')->ids();

        $this->assertInternalType('array', $ids);
        $this->assertCount(3, $ids);
        $this->assertEquals([1, 2, 3], $ids);

        $ids = $this->pool->find(Writer::class)->orderBy('`id`')->joinTable('writer_groups')->where('`writer_groups`.`group_id` = ?', 1)->ids();

        $this->assertInternalType('array', $ids);
        $this->assertCount(2, $ids);
        $this->assertEquals([1, 2], $ids);

        $ids = $this->pool->find(Writer::class)->orderBy('`id`')->joinTable('writer_groups')->where('`writer_groups`.`group_id` = ?', 2)->ids();

        $this->assertInternalType('array', $ids);
        $this->assertCount(1, $ids);
        $this->assertEquals([3], $ids);
    }
}
