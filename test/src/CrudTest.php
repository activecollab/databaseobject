<?php
namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\AwesomeWriter;
use DateTime;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class CrudTest extends TestCase
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
     * Test if instances get default values pre-populated
     */
    public function testNewInstancesGetDefaultFieldValues()
    {
        $unknown_writer = new Writer($this->pool, $this->connection);
        $this->assertEquals('Unknown Writer', $unknown_writer->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFieldsWithDefaultValueCantBeNull()
    {
        $unknown_writer = new Writer($this->pool, $this->connection);
        $unknown_writer->setName(null);
    }

    /**
     * Test if ID is primary key
     */
    public function testIdIsPrimaryKey()
    {
        $unknown_writer = new Writer($this->pool, $this->connection);

        $this->assertTrue($unknown_writer->isPrimaryKey('id'));
        $this->assertFalse($unknown_writer->isPrimaryKey('name'));
    }

    /**
     * Test get object by ID
     */
    public function testGetById()
    {
        /** @var Writer $tolstoy */
        $tolstoy = $this->pool->getById(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());
        $this->assertSame(1, $tolstoy->getId());
        $this->assertSame('Leo Tolstoy', $tolstoy->getName());
        $this->assertSame('1828-09-09', $tolstoy->getBirthday());
    }

    /**
     * Test if getById is subclassing aware
     */
    public function testSublassingAwareGetById()
    {
        /** @var Writer $tolstoy */
        $tolstoy = $this->pool->getById(AwesomeWriter::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());
        $this->assertSame(1, $tolstoy->getId());
        $this->assertSame('Leo Tolstoy', $tolstoy->getName());
        $this->assertSame('1828-09-09', $tolstoy->getBirthday());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetByIdThrowsAnExceptionOnUnregisteredType()
    {
        $this->pool->getById(DateTime::class, 1);
    }

    /**
     * Object create
     */
    public function testCreate()
    {
        $chekhov = new Writer($this->pool, $this->connection);

        $chekhov->setName('Anton Chekhov');
        $chekhov->setBirthday(new DateTime('1860-01-29'));

        $chekhov->save();

        $this->assertTrue($chekhov->isLoaded());
        $this->assertSame(4, $chekhov->getId());
        $this->assertSame('Anton Chekhov', $chekhov->getName());
        $this->assertEquals('1860-01-29', $chekhov->getBirthday()->format('Y-m-d'));
    }

    /**
     * Test record update
     */
    public function testUpdate()
    {
        /** @var Writer $tolstoy */
        $tolstoy = $this->pool->getById(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());

        $tolstoy->setName('Lev Nikolayevich Tolstoy');
        $tolstoy->save();

        $tolstoy = $this->pool->reload(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());

        $this->assertEquals('Lev Nikolayevich Tolstoy', $tolstoy->getName());
    }

    /**
     * Test if we can change ID to a new value that is not yet reserved
     */
    public function testChangeIdToNewRecord()
    {
        $chekhov = new Writer($this->pool, $this->connection);

        $chekhov->setName('Anton Chekhov');
        $chekhov->setBirthday(new DateTime('1860-01-29'));

        $chekhov->save();

        $this->assertFalse($chekhov->isPrimaryKeyModified());

        $this->assertSame(4, $chekhov->getId());

        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 4));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 18));

        // Update primary key and save the object
        $chekhov->setId(18);
        $this->assertTrue($chekhov->isPrimaryKeyModified());

        $chekhov->save();

        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 4));
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 18));
    }

    /**
     * @expectedException \LogicException
     */
    public function testChangeIdToExistingRecord()
    {
        $chekhov = new Writer($this->pool, $this->connection);

        $chekhov->setName('Anton Chekhov');
        $chekhov->setBirthday(new DateTime('1860-01-29'));

        $chekhov->save();

        $this->assertSame(4, $chekhov->getId());

        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 1));
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 4));

        $chekhov->setId(1);
        $chekhov->save();
    }

    /**
     * Test delete
     */
    public function testDelete()
    {
        $this->assertTrue($this->pool->exists(Writer::class, 1));

        /** @var Writer $tolstoy */
        $tolstoy = $this->pool->getById(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());

        $tolstoy->delete();

        $this->assertFalse($this->pool->exists(Writer::class, 1));
    }
}