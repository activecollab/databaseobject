<?php
namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseConnection\Result\Result;
use ActiveCollab\DatabaseObject\Finder;
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
     * @expectedException \InvalidArgumentException
     */
    public function testCountThrowsAnExceptionOnUnregisteredType()
    {
        $this->pool->count(DateTime::class);
    }

    /**
     * Test count by the given conditions
     */
    public function testCountWithConditions()
    {
        $this->assertEquals(2, $this->pool->count(Writer::class, ['birthday > ?', new DateTime('1800-01-01')]));
    }

    /**
     * Test if find() method returns Finder instance
     */
    public function testFindReturnsFinder()
    {
        $finder = $this->pool->find(Writer::class);

        $this->assertInstanceOf(Finder::class, $finder);
        $this->assertEquals(Writer::class, $finder->getType());
    }

    /**
     * Test find all writers from the database
     */
    public function testFindAll()
    {
        /** @var Result $result */
        $result = $this->pool->find(Writer::class)->all();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount(3, $result);

        foreach ($result as $writer) {
            $this->assertInstanceOf(Writer::class, $writer);
        }
    }

    /**
     * Test find first record
     */
    public function testFindFirst()
    {
        /** @var Writer $should_be_pushkin */
        $should_be_pushkin = $this->pool->find(Writer::class)->orderBy('`birthday`')->first();

        $this->assertInstanceOf(Writer::class, $should_be_pushkin);
        $this->assertTrue($should_be_pushkin->isLoaded());
        $this->assertEquals('Alexander Pushkin', $should_be_pushkin->getName());
    }

    /**
     * Test find all ID-s
     */
    public function testFindAllIds()
    {
        $ids = $this->pool->find(Writer::class)->ids();

        $this->assertInternalType('array', $ids);
        $this->assertCount(3, $ids);
    }

    /**
     * Test find by conditions
     */
    public function testFindByConditions()
    {
        /** @var Writer $should_be_leo */
        $should_be_leo = $this->pool->find(Writer::class)->where('`name` LIKE ?', '%Leo%')->first();

        $this->assertInstanceOf(Writer::class, $should_be_leo);
        $this->assertTrue($should_be_leo->isLoaded());
        $this->assertEquals('Leo Tolstoy', $should_be_leo->getName());
    }

    /**
     * Test find ID-s by conditions
     */
    public function testFindIdsByCondition()
    {
        $ids = $this->pool->find(Writer::class)->where('`name` LIKE ?', '%Leo%')->ids();

        $this->assertInternalType('array', $ids);
        $this->assertCount(1, $ids);
        $this->assertSame(1, $ids[0]);
    }

    /**
     * Test limit and offset
     */
    public function testOffset()
    {
        $result = $this->pool->find(Writer::class)->orderBy('`birthday`')->limit(1, 1)->all();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount(1, $result);

        /** @var Writer $should_be_fyodor */
        $should_be_fyodor = $result[0];

        $this->assertInstanceOf(Writer::class, $should_be_fyodor);
        $this->assertTrue($should_be_fyodor->isLoaded());
        $this->assertEquals('Fyodor Dostoyevsky', $should_be_fyodor->getName());
    }

    /**
     * Test if dependencies are properly set to hydrated objects so they are fully functional
     */
    public function testHydratedObjectsAreFullyFunctional()
    {
        /** @var Writer $leo */
        $should_be_leo = $this->pool->find(Writer::class)->where('`name` = ?', 'Leo Tolstoy')->first();

        $this->assertInstanceOf(Writer::class, $should_be_leo);
        $this->assertTrue($should_be_leo->isLoaded());
        $this->assertEquals('Leo Tolstoy', $should_be_leo->getName());

        $should_be_leo->setName('Lev Nikolayevich Tolstoy');
        $should_be_leo->save();

        $should_be_leo = $this->pool->reload(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $should_be_leo);
        $this->assertTrue($should_be_leo->isLoaded());

        $this->assertEquals('Lev Nikolayevich Tolstoy', $should_be_leo->getName());
    }
}