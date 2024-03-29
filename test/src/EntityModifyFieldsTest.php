<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use InvalidArgumentException;

class EntityModifyFieldsTest extends WritersTypeTestCase
{
    /**
     * @var Writer|EntityInterface
     */
    private $writer;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->writer = $this->pool->getById(Writer::class, 1);
        $this->assertInstanceOf(Writer::class, $this->writer);
    }

    public function testMissingFieldCantBeSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'not a field' is not a known field");
        $this->writer->setFieldValue('not a field', 'yes, a value');
    }

    public function testGetOldValue()
    {
        $started_with = $this->writer->getName();

        $this->writer->setName('Lew Nikolajewitsch Tolstoi');

        $this->assertSame('Lew Nikolajewitsch Tolstoi', $this->writer->getName());
        $this->assertSame($started_with, $this->writer->getOldFieldValue('name'));

        $this->assertEquals(['name' => $started_with], $this->writer->getOldValues());
    }

    public function testRevetField()
    {
        $started_with = $this->writer->getName();

        $this->assertFalse($this->writer->isModified());
        $this->assertFalse($this->writer->isModifiedField('name'));

        $this->writer->setName('Lew Nikolajewitsch Tolstoi');

        $this->assertTrue($this->writer->isModified());
        $this->assertTrue($this->writer->isModifiedField('name'));

        $this->writer->revertField('name');

        $this->assertTrue($this->writer->isModified());
        $this->assertTrue($this->writer->isModifiedField('name'));

        $this->assertSame($started_with, $this->writer->getName());
    }
}
