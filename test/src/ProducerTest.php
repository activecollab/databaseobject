<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\CustomProducer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Users\Base\User;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\AwesomeWriter;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DateValue\DateValue;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class ProducerTest extends WritersTypeTestCase
{
    /**
     * Test if default producer is used by default.
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
     * Test if custom producer can be set for registered type.
     */
    public function testCustomProducerCanBeSetForType()
    {
        $this->pool->registerProducer(Writer::class, new CustomProducer($this->connection, $this->pool, $this->logger));

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
     * Test if custom producer can be set for subtype.
     */
    public function testCustomProducerCanBeSetForSubtype()
    {
        $this->pool->registerProducer(AwesomeWriter::class, new CustomProducer($this->connection, $this->pool, $this->logger));

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
     * Test if custom producer can be set for registered type.
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

    public function testCustomProducerCantBeSetForUnregisteredType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->assertFalse($this->pool->isTypeRegistered(User::class));
        $this->pool->registerProducer(User::class, new CustomProducer($this->connection, $this->pool, $this->logger));
    }

    public function testCustomProducerCantBeSetTwice()
    {
        $this->expectException(LogicException::class);
        $this->pool->registerProducer(Writer::class, new CustomProducer($this->connection, $this->pool, $this->logger));
        $this->pool->registerProducer(AwesomeWriter::class, new CustomProducer($this->connection, $this->pool, $this->logger));
    }

    public function testCustomProducerRequiresProducerInterface()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Class 'stdClass' does not implement 'ActiveCollab\DatabaseObject\ProducerInterface' interface");
        $this->pool->registerProducerByClass(Writer::class, \stdClass::class);
    }

    public function testUnsavedObjectsCantBeModified()
    {
        $this->expectException(RuntimeException::class);
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-20'),
        ], false);

        $this->assertTrue($object->isNew());

        $this->pool->modify($object, [
            'name' => 'Anton Pavlovich Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);
    }

    /**
     * Test modify using default producer.
     */
    public function testModifyUsingDefaultProducer()
    {
        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-20'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertFalse($object->modified_using_custom_producer);

        $this->pool->modify($object, [
            'name' => 'Anton Pavlovich Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);

        $this->assertEquals('Anton Pavlovich Chekhov', $object->getName());
        $this->assertEquals('1860-01-29', $object->getBirthday()->format('Y-m-d'));
        $this->assertFalse($object->modified_using_custom_producer);
    }

    /**
     * Test modify using custom producer.
     */
    public function testModifyUsingCustomProducer()
    {
        $this->pool->registerProducerByClass(Writer::class, CustomProducer::class);

        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-20'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertFalse($object->modified_using_custom_producer);

        $this->pool->modify($object, [
            'name' => 'Anton Pavlovich Chekhov',
            'birthday' => new DateValue('1860-01-29'),
        ]);

        $this->assertEquals('Anton Pavlovich Chekhov', $object->getName());
        $this->assertEquals('1860-01-29', $object->getBirthday()->format('Y-m-d'));
        $this->assertTrue($object->modified_using_custom_producer);
    }

    public function testUnsavedObjectsCantBeScrapped()
    {
        $this->expectException(RuntimeException::class);
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-20'),
        ], false);

        $this->assertTrue($object->isNew());

        $this->pool->scrap($object);
    }

    /**
     * Test instance scrap using default producer.
     */
    public function testScrapUsingDefaultProducer()
    {
        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-20'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertFalse($object->isNew());
        $this->assertFalse($object->is_scrapped);
        $this->assertFalse($object->scrapped_using_custom_producer);

        $object = $this->pool->scrap($object);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertFalse($object->isNew());
        $this->assertTrue($object->is_scrapped);
        $this->assertFalse($object->scrapped_using_custom_producer);
    }

    /**
     * Test instance scrap using custom producer.
     */
    public function testScrapUsingCustomProducer()
    {
        $this->pool->registerProducerByClass(Writer::class, CustomProducer::class);

        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-20'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertFalse($object->isNew());
        $this->assertFalse($object->is_scrapped);
        $this->assertFalse($object->scrapped_using_custom_producer);

        $object = $this->pool->scrap($object);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertFalse($object->isNew());
        $this->assertTrue($object->is_scrapped);
        $this->assertTrue($object->scrapped_using_custom_producer);
    }

    /**
     * Test instance force delete.
     */
    public function testForceDelete()
    {
        /** @var Writer $object */
        $object = $this->pool->produce(Writer::class, [
            'name' => 'Anton Chekhov',
            'birthday' => new DateValue('1860-01-20'),
        ]);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertFalse($object->isNew());
        $this->assertFalse($object->is_scrapped);
        $this->assertFalse($object->scrapped_using_custom_producer);

        $object = $this->pool->scrap($object, true);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertTrue($object->isNew());
        $this->assertFalse($object->is_scrapped);
        $this->assertFalse($object->scrapped_using_custom_producer);
    }
}
