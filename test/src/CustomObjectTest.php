<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\CustomObject\CustomObjectPool;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\SpecialWriter;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class CustomObjectTest extends TestCase
{
    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->pool = new CustomObjectPool($this->connection);
        $this->pool->registerType(SpecialWriter::class);

        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        $create_table = $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci,
            `birthday` date NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateValue('1828-09-09'), 'Alexander Pushkin', new DateValue('1799-06-06'), 'Fyodor Dostoyevsky', new DateValue('1821-11-11'));
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        parent::tearDown();
    }

    /**
     * Test if we are using custom pool
     */
    public function testUsingCustomPool()
    {
        $this->assertInstanceOf(CustomObjectPool::class, $this->pool);
    }

    /**
     * Test instantination via getById() method
     */
    public function testGetByIdInstantination()
    {
        $special_writer = $this->pool->getById(SpecialWriter::class, 1);

        $this->assertInstanceOf(SpecialWriter::class, $special_writer);
        $this->assertTrue($special_writer->isSpecial());
    }

    /**
     * Test instantination via finder
     */
    public function testFindIdInstantination()
    {
        $special_writer = $this->pool->find(SpecialWriter::class)->where('id = ?', 1)->first();

        $this->assertInstanceOf(SpecialWriter::class, $special_writer);
        $this->assertTrue($special_writer->isSpecial());
    }

    public function testInstantinationUsingProducer()
    {
        $special_writer = $this->pool->produce(SpecialWriter::class, [
            'name' => 'Special Writer',
            'birthday' => '2013-10-02',
        ]);

        $this->assertInstanceOf(SpecialWriter::class, $special_writer);
        $this->assertTrue($special_writer->isSpecial());
    }
}
