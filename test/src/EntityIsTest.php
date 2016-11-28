<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class EntityIsTest extends WritersTypeTestCase
{
    /**
     * @var Writer|EntityInterface
     */
    private $writer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->writer = $this->pool->getById(Writer::class, 1);
        $this->assertInstanceOf(Writer::class, $this->writer);
    }

    public function testFailOnObjectThatsNotAnEntity()
    {
        $this->assertFalse($this->writer->is(new \stdClass()));
    }

    public function testFailWhenNotAllFieldsAreTheSame()
    {
        $field_values = [];

        foreach ($this->writer->getFields() as $field_name) {
            $field_values[$field_name] = $this->writer->getFieldValue($field_name);
        }

        array_shift($field_values); // Remove one element

        $new_writer = $this->pool->produce(Writer::class, $field_values, false);

        $this->assertFalse($new_writer->is($this->writer));
    }

    public function testPassWhenAllFieldsAreTheSame()
    {
        $writer_field_values = [];

        foreach ($this->writer->getFields() as $field_name) {
            $writer_field_values[$field_name] = $this->writer->getFieldValue($field_name);
        }

        $new_writer = $this->pool->produce(Writer::class, $writer_field_values, false);

        $new_writer_field_values = [];

        foreach ($new_writer->getFields() as $field_name) {
            $new_writer_field_values[$field_name] = $new_writer->getFieldValue($field_name);
        }

        $this->assertEquals($new_writer_field_values, $writer_field_values);

        $this->assertTrue($new_writer->is($this->writer));
    }

    public function testFailWhenLoadedObjectsDoNotMatch()
    {
        $this->assertFalse($this->writer->is($this->pool->reload(Writer::class, $this->writer->getId() + 1)));
    }

    public function testPassWhenLoadedObjectsMatch()
    {
        $this->assertTrue($this->writer->is($this->pool->reload(Writer::class, $this->writer->getId())));
    }
}
