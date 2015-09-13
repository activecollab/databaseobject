<?php
namespace ActiveCollab\DatabaseObject\Test;


use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
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
     *
     */
    public function testNewInstancesGetDefaultFieldValues()
    {
        $unknown_writer = new Writer();
        $this->assertEquals('Unknown Writer', $unknown_writer->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFieldsWithDefaultValueCantBeNull()
    {
        $unknown_writer = new Writer();
        $unknown_writer->setName(null);
    }

    /**
     * Object create
     */
    public function testCreate()
    {
        $chekhov = new Writer();

        $chekhov->setName('Anton Chekhov');
        $chekhov->setBirthday(new DateTime('1860-01-29'));

        $chekhov->save();

        $this->assertTrue($chekhov->isLoaded());
        $this->assertSame(4, $chekhov->getId());
        $this->assertSame('Anton Chekhov', $chekhov->getName());
        $this->assertEquals('1860-01-29', $chekhov->getBirthday()->format('Y-m-d'));
    }
}