<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\CustomProducer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Users\Base\User;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\AwesomeWriter;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class ProducerTest extends TestCase
{
    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        $create_table = $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci,
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateValue('1828-09-09'), 'Alexander Pushkin', new DateValue('1799-06-06'), 'Fyodor Dostoyevsky', new DateValue('1821-11-11'));

        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
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
     * Test if default producer is used by default
     */
    public function testDefaultProducerIsUsedByDefault()
    {
        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertNull($object->custom_attribute_value);
    }

    /**
     * Test if custom producer can be set for registered type
     */
    public function testCustomProducerCanBeSetForType()
    {
        $this->pool->registerProducer(Writer::class, new CustomProducer($this->connection, $this->pool));

        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
            'custom_producer_set_custom_attribute_to' => 1234,
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertSame(1234, $object->custom_attribute_value);
    }

    /**
     * Test if custom producer can be set for subtype
     */
    public function testCustomProducerCanBeSetForSubtype()
    {
        $this->pool->registerProducer(AwesomeWriter::class, new CustomProducer($this->connection, $this->pool));

        /** @var Writer $object */
        $object = $this->pool->produce(AwesomeWriter::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
            'custom_producer_set_custom_attribute_to' => 1234,
        ]);

        $this->assertInstanceOf(AwesomeWriter::class, $object);
        $this->assertSame(1234, $object->custom_attribute_value);
    }

    /**
     * Test if custom producer can be set for registered type
     */
    public function testCustomProducerCanBeSetByClassName()
    {
        $this->pool->registerProducerByClass(Writer::class, CustomProducer::class);

        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-29'),
            'custom_producer_set_custom_attribute_to' => 1234,
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertSame(1234, $object->custom_attribute_value);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCustomProducerCantBeSetForUnregisteredType()
    {
        $this->assertFalse($this->pool->isTypeRegistered(User::class));
        $this->pool->registerProducer(User::class, new CustomProducer($this->connection, $this->pool));
    }

    /**
     * @expectedException \LogicException
     */
    public function testCustomProducerCantBeSetTwice()
    {
        $this->pool->registerProducer(Writer::class, new CustomProducer($this->connection, $this->pool));
        $this->pool->registerProducer(AwesomeWriter::class, new CustomProducer($this->connection, $this->pool));
    }
}