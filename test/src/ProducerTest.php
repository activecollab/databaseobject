<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\CustomProducer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Users\Base\User;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\AwesomeWriter;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class ProducerTest extends WritersTypeTestCase
{
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