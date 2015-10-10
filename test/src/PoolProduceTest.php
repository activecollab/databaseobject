<?php
namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\AwesomeWriter;
use ActiveCollab\DatabaseObject\Validator;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class PoolProduceTest extends TestCase
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
     * Test if pool can produce an instance of registered type
     */
    public function testPoolCanProduceRegisteredType()
    {
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
    }

    /**
     * Test if pool can produce a new instance of subclass of registered type
     */
    public function testPoolCanProduceSubclassOfRegisteredType()
    {
        $object = $this->pool->produce(AwesomeWriter::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertInstanceOf(AwesomeWriter::class, $object);
    }

    /**
     * Test if produce can set object attributes
     */
    public function testProduceCanSetAttributes()
    {
        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);
        $this->assertInstanceOf(Writer::class, $object);
        $this->assertEquals('Anton Chekhov', $object->getName());
    }

    /**
     * Test if produce saves objects by default
     */
    public function testProduceSavesByDefault()
    {
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);
        $this->assertInstanceOf(Writer::class, $object);
        $this->assertTrue($object->isLoaded());
    }

    /**
     * Test if produce can skip save when requested
     */
    public function testProduceCanSkipSave()
    {
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
        ], false);
        $this->assertInstanceOf(Writer::class, $object);
        $this->assertFalse($object->isLoaded());
        $this->assertEquals('Anton Chekhov', $object->getName());
    }

    /**
     * Test produce() and save() calls save object to the pool
     */
    public function testProduceSavesToObjectPool()
    {
        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
    }

    /**
     * Test if produced object can be forgotten
     */
    public function testProducedObjectCanBeForgotten()
    {
        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertTrue($this->pool->isInPool(Writer::class, $object->getId()));

        $this->pool->forget(Writer::class, $object->getId());

        $this->assertFalse($this->pool->isInPool(Writer::class, $object->getId()));
    }

    /**
     * Test if updates to the object are reflected back to the pool
     */
    public function testProducedObjectUpdateIsSavedToPool()
    {
        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);

        $this->pool->forget(Writer::class, $object->getId());

        $this->assertFalse($this->pool->isInPool(Writer::class, $object->getId()));

        // Update instance that is no longer in the pool
        $object->setName('Anton Pavlovich Chekhov');
        $object->save();

        /** @var Writer $object_from_pool */
        $object_from_pool_second_take = $this->pool->getById(Writer::class, $object->getId());
        $this->assertInstanceOf(Writer::class, $object_from_pool_second_take);
        $this->assertEquals('Anton Pavlovich Chekhov', $object_from_pool_second_take->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnUnknonwClass()
    {
        $this->pool->produce('Definitely not a Class');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnNonObjectClass()
    {
        $this->pool->produce(Validator::class);
    }
}