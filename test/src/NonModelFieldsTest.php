<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\StatSnapshots\StatsSnapshot;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class NonModelFieldsTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection->execute("CREATE TABLE IF NOT EXISTS `stats_snapshots` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `account_id` INT UNSIGNED DEFAULT NULL,
            `day` DATETIME DEFAULT NULL,
            `is_used_on_day` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
            `stats` JSON DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `account_id` (`account_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->pool->registerType(StatsSnapshot::class);
    }

    public function tearDown()
    {
        $this->connection->dropTable('stats_snapshots');

        parent::tearDown();
    }

    public function testStatsCanBeSaved()
    {
        $stats = $this->pool->produce(StatsSnapshot::class, [
            'account_id' => 1,
            'day' => new DateValue(),
            'is_used_on_day' => true,
            'stats' => [1, 2, 3],
        ]);

        $this->assertInstanceOf(StatsSnapshot::class, $stats);
    }
}
