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
use JsonSerializable;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class SerializeTest extends WritersTypeTestCase
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

    public function testEntityIsSerializable()
    {
        $this->assertInstanceOf(JsonSerializable::class, $this->writer);
    }

    public function testSerialization()
    {
        $data = json_decode(json_encode($this->writer), true);
        $this->assertInternalType('array', $data);

        $this->assertArrayHasKey('id', $data);
        $this->assertSame($this->writer->getId(), $data['id']);

        $this->assertArrayHasKey('type', $data);
        $this->assertSame(Writer::class, $data['type']);

        $this->assertArrayHasKey('name', $data);
        $this->assertSame($this->writer->getName(), $data['name']);

        $this->assertArrayHasKey('birthday', $data);
        $this->assertSame($this->writer->getBirthday()->getTimestamp(), $data['birthday']);
    }

    public function testExtendedSerializationIsEmptyByDefault()
    {
        $this->assertInternalType('array', $this->writer->jsonSerializeDetails());
        $this->assertEmpty($this->writer->jsonSerializeDetails());
    }
}
