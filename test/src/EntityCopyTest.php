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
use ActiveCollab\DateValue\DateTimeValueInterface;
use ActiveCollab\DateValue\DateValueInterface;

class EntityCopyTest extends WritersTypeTestCase
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

    public function testCopyWithSave()
    {
        $writer_copy = $this->writer->copy(true);

        $this->assertInstanceOf(Writer::class, $writer_copy);
        $this->assertTrue($writer_copy->isLoaded());
        $this->assertNotEquals($this->writer->getId(), $writer_copy->getId());

        foreach ($writer_copy->getEntityFields() as $field) {
            if ($writer_copy->isPrimaryKey($field)) {
                continue;
            }

            $old_writer_value = $this->writer->getFieldValue($field);
            $writer_copy_value = $writer_copy->getFieldValue($field);

            if (($old_writer_value instanceof DateValueInterface && $writer_copy_value instanceof DateValueInterface) || ($old_writer_value instanceof DateTimeValueInterface && $writer_copy_value instanceof DateTimeValueInterface)) {
                $this->assertSame($old_writer_value->getTimestamp(), $writer_copy_value->getTimestamp());
            } else {
                $this->assertSame($old_writer_value, $writer_copy_value);
            }
        }
    }

    public function testCopyWithoutSave()
    {
        $writer_copy = $this->writer->copy();

        $this->assertInstanceOf(Writer::class, $writer_copy);
        $this->assertFalse($writer_copy->isLoaded());
        $this->assertEmpty($writer_copy->getId());

        foreach ($writer_copy->getEntityFields() as $field) {
            if ($writer_copy->isPrimaryKey($field)) {
                continue;
            }

            $old_writer_value = $this->writer->getFieldValue($field);
            $writer_copy_value = $writer_copy->getFieldValue($field);

            if (($old_writer_value instanceof DateValueInterface && $writer_copy_value instanceof DateValueInterface) || ($old_writer_value instanceof DateTimeValueInterface && $writer_copy_value instanceof DateTimeValueInterface)) {
                $this->assertSame($old_writer_value->getTimestamp(), $writer_copy_value->getTimestamp());
            } else {
                $this->assertSame($old_writer_value, $writer_copy_value);
            }
        }
    }
}
