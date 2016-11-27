<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\StatSnapshots\StatsSnapshot;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class GeneratedFieldsTest extends TestCase
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
        $this->assertInstanceOf(StatsSnapshot::class, $this->produceSnapshot());
    }

    public function testFieldsVsGeneratedFieldsChecks()
    {
        $snapshot = $this->produceSnapshot();

        $this->assertTrue($snapshot->fieldExists('account_id'));
        $this->assertTrue($snapshot->fieldExists('is_used_on_day'));

        $this->assertFalse($snapshot->generatedFieldExists('account_id'));
        $this->assertTrue($snapshot->generatedFieldExists('is_used_on_day'));

        $this->assertFalse($snapshot->isGeneratedField('account_id'));
        $this->assertTrue($snapshot->isGeneratedField('is_used_on_day'));
    }

    public function testIsUsedOnDayIsGeneratedField()
    {
        $this->assertNotContains('is_used_on_day', $this->pool->getTypeFields(StatsSnapshot::class));
        $this->assertContains('is_used_on_day', $this->pool->getGeneratedTypeFields(StatsSnapshot::class));
    }

    /**
     * @expectedException \Exception
     */
    public function testGeneratedFieldCantBeSetDuringProduction()
    {
        $this->pool->produce(StatsSnapshot::class, [
            'account_id' => 1,
            'day' => new DateValue(),
            'is_used_on_day' => true,
            'stats' => [
                'users' => 1,
            ],
        ]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Generated field is_used_on_day cannot be set by directly assigning a value
     */
    public function testGeneratedFieldCantBeSetUsingSetField()
    {
        $this->produceSnapshot()->setFieldValue('is_used_on_day', true);
    }

    /**
     * @param  array|null                    $stats
     * @return StatsSnapshot|EntityInterface
     */
    private function produceSnapshot(array $stats = null)
    {
        if ($stats === null) {
            $stats = [
                'users' => 2,
                'projects' => 5,
                'storage_used' => 1024 * 1024 * 1024,
            ];
        }

        $snapshot = $this->pool->produce(StatsSnapshot::class, [
            'account_id' => 1,
            'day' => new DateValue(),
            'stats' => $stats,
        ]);
        $this->assertInstanceOf(StatsSnapshot::class, $snapshot);

        return $snapshot;
    }
}
