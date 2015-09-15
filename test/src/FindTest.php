<?php
namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseConnection\Result\Result;
use DateTime;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class FindTest extends TestCase
{
    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection->execute('DROP TABLE IF EXISTS `writers`');

        $create_table = $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateTime('1828-09-09'), 'Alexander Pushkin', new DateTime('1799-06-06'), 'Fyodor Dostoyevsky', new DateTime('1821-11-11'));

        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        $this->connection->execute('DROP TABLE IF EXISTS `writers`');

        parent::tearDown();
    }

    /**
     * Test count all
     */
    public function testCount()
    {
        $this->assertEquals(3, $this->pool->count(Writer::class));
    }

    /**
     * Test count by the given conditions
     */
    public function testCountWithConditions()
    {
        $this->assertEquals(2, $this->pool->count(Writer::class, ['birthday > ?', new DateTime('1800-01-01')]));
    }

    /**
     * Test find all writers from the database
     */
    public function testFindAll()
    {
        /** @var Result $result */
        $result = $this->pool->find(Writer::class);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount(3, $result);

        foreach ($result as $writer) {
            $this->assertInstanceOf(Writer::class, $writer);
        }
    }
}