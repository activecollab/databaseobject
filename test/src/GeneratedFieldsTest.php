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
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\DateValue\DateValue;
use ActiveCollab\DateValue\DateValueInterface;

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
            `day` DATE DEFAULT NULL,
            `is_used_on_day` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
            `stats` JSON DEFAULT NULL,
            `plan_name` VARCHAR(191) AS (JSON_UNQUOTE(JSON_EXTRACT(`stats`, '$.plan_name'))),
            `number_of_users` INT AS (JSON_UNQUOTE(JSON_EXTRACT(`stats`, '$.users'))),
            PRIMARY KEY (`id`),
            INDEX `account_id` (`account_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // For the test purposes, each odd day will be set as used, and each even will be set as not used.
        $this->connection->execute('CREATE TRIGGER `insert_stats_snapshots` BEFORE INSERT ON `stats_snapshots` FOR EACH ROW SET NEW.`is_used_on_day` = MOD(DAY(NEW.`day`), 2);');
        $this->connection->execute('CREATE TRIGGER `update_stats_snapshots` BEFORE UPDATE ON `stats_snapshots` FOR EACH ROW SET NEW.`is_used_on_day` = MOD(DAY(NEW.`day`), 2);');

        $this->pool->registerType(StatsSnapshot::class);
    }

    public function tearDown()
    {
        $this->connection->dropTable('stats_snapshots');
        $this->connection->execute('DROP TRIGGER IF EXISTS `insert_stats_snapshots`');
        $this->connection->execute('DROP TRIGGER IF EXISTS `update_stats_snapshots`');

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
     * @expectedException \LogicException
     * @expectedExceptionMessage Generated field is_used_on_day cannot be set by directly assigning a value
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

    public function testPoolUsesTheCorrectListOfGeneratedFields()
    {
        $snapshot = $this->produceSnapshot();
        $this->assertSame($snapshot->getGeneratedFields(), $this->pool->getGeneratedTypeFields(StatsSnapshot::class));
    }

    public function testPoolIncludesGeneratedFieldsInTypeFieldsList()
    {
        $generated_type_fields = $this->pool->getGeneratedTypeFields(StatsSnapshot::class);
        $this->assertInternalType('array', $generated_type_fields);
        $this->assertContains('is_used_on_day', $generated_type_fields);

        $escaped_type_fields = $this->pool->getEscapedTypeFields(StatsSnapshot::class);
        $this->assertInternalType('string', $escaped_type_fields);
        $this->assertContains('is_used_on_day', $escaped_type_fields);
    }

    public function testGeneratedFieldsAreHydrated()
    {
        $this->assertSame(0, $this->connection->count('stats_snapshots'));
        $insert_id = $this->connection->insert('stats_snapshots', [
            'account_id' => 1,
            'day' => new DateTimeValue('2016-11-27'),
            'is_used_on_day' => true,
            'stats' => json_encode(['users' => 1]),
        ]);
        $this->assertSame(1, $insert_id);

        $row = $this->connection->executeFirstRow('SELECT * FROM stats_snapshots WHERE id = ?', $insert_id);
        $this->assertInternalType('array', $row);
        $this->assertArrayHasKey('is_used_on_day', $row);
        $this->assertTrue($row['is_used_on_day']);

        /** @var StatsSnapshot $snapshot */
        $snapshot = $this->pool->getById(StatsSnapshot::class, $insert_id);
        $this->assertInstanceOf(StatsSnapshot::class, $snapshot);
        $this->assertTrue($snapshot->isUsedOnDay());
    }

    public function testGeneratedFieldsAreRefreshedOnInsert()
    {
        $odd_day = $this->produceSnapshot(1, new DateValue('2016-11-27'));
        $even_day = $this->produceSnapshot(1, new DateValue('2016-11-28'));

        $this->assertTrue($odd_day->isUsedOnDay());
        $this->assertFalse($even_day->isUsedOnDay());
    }

    public function testGeneratedFieldsAreRefreshedOnUpdate()
    {
        $odd_day = $this->produceSnapshot(1, new DateValue('2016-11-27'));
        $this->assertTrue($odd_day->isUsedOnDay());

        $odd_day->setDay(new DateValue('2016-11-28'))->save();
        $this->assertFalse($odd_day->isUsedOnDay());
    }

    public function testJsonExtractionsDefaultToNull()
    {
        /** @var StatsSnapshot $snapshot */
        $snapshot = $this->pool->produce(StatsSnapshot::class, [
            'account_id' => 1,
            'day' => new DateValue(),
            'stats' => [],
        ]);
        $this->assertInstanceOf(StatsSnapshot::class, $snapshot);

        $this->assertNull($snapshot->getPlanName());
        $this->assertNull($snapshot->getNumberOfUsers());
    }

    public function testJsonExtractionsAreCastedOnHydation()
    {
        $insert_id = $this->connection->insert('stats_snapshots', [
            'account_id' => 1,
            'day' => new DateTimeValue('2016-11-27'),
            'is_used_on_day' => true,
            'stats' => json_encode([
                'plan_name' => 'MEGA',
                'users' => 10,
            ]),
        ]);
        $this->assertSame(1, $insert_id);

        /** @var StatsSnapshot $snapshot */
        $snapshot = $this->pool->getById(StatsSnapshot::class, $insert_id);

        $this->assertSame('MEGA', $snapshot->getPlanName());
        $this->assertSame(10, $snapshot->getNumberOfUsers());
    }

    public function testJsonExtractionsAreCastedOnInsert()
    {
        /** @var StatsSnapshot $snapshot */
        $snapshot = $this->pool->produce(StatsSnapshot::class, [
            'account_id' => 1,
            'day' => new DateValue(),
            'stats' => [
                'plan_name' => 'MEGA',
                'users' => 10,
            ],
        ]);
        $this->assertInstanceOf(StatsSnapshot::class, $snapshot);

        $this->assertSame('MEGA', $snapshot->getPlanName());
        $this->assertSame(10, $snapshot->getNumberOfUsers());
    }

    public function testJsonExtractionsAreCastedOnUpdate()
    {
        /** @var StatsSnapshot $snapshot */
        $snapshot = $this->pool->produce(StatsSnapshot::class, [
            'account_id' => 1,
            'day' => new DateValue(),
            'stats' => [],
        ]);
        $this->assertInstanceOf(StatsSnapshot::class, $snapshot);

        $this->assertNull($snapshot->getPlanName());
        $this->assertNull($snapshot->getNumberOfUsers());

        $snapshot->setStats([
            'plan_name' => 'MEGA',
            'users' => 10,
        ])->save();

        $this->assertSame('MEGA', $snapshot->getPlanName());
        $this->assertSame(10, $snapshot->getNumberOfUsers());
    }

    /**
     * @param  int|null                      $account_id
     * @param  DateValueInterface|null       $day
     * @param  array|null                    $stats
     * @return StatsSnapshot|EntityInterface
     */
    private function produceSnapshot($account_id = null, DateValueInterface $day = null, array $stats = null)
    {
        if ($account_id === null) {
            $account_id = 1;
        }

        if ($day === null) {
            $day = new DateValue();
        }

        if ($stats === null) {
            $stats = [
                'users' => 2,
                'projects' => 5,
                'storage_used' => 1024 * 1024 * 1024,
            ];
        }

        $snapshot = $this->pool->produce(StatsSnapshot::class, [
            'account_id' => $account_id,
            'day' => $day,
            'stats' => $stats,
        ]);
        $this->assertInstanceOf(StatsSnapshot::class, $snapshot);

        return $snapshot;
    }
}
