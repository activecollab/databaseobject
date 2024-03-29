<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Producer;
use ActiveCollab\DatabaseObject\ProducerInterface;
use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\CustomProducer;
use ActiveCollab\DatabaseObject\Validator;
use InvalidArgumentException;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class DefaultProducerTest extends TestCase
{
    public function testDefaultProducer()
    {
        $this->assertInstanceOf(ProducerInterface::class, $this->pool->getDefaultProducer());
    }

    public function testDefaultProducerCanBeSet()
    {
        $this->assertInstanceOf(Producer::class, $this->pool->getDefaultProducer());
        $this->pool->setDefaultProducer(new CustomProducer($this->connection, $this->pool, $this->logger));
        $this->assertInstanceOf(CustomProducer::class, $this->pool->getDefaultProducer());
    }

    public function testCustomProducerCanBeSetByClassName()
    {
        $this->assertInstanceOf(Producer::class, $this->pool->getDefaultProducer());
        $this->pool->setDefaultProducerClass(CustomProducer::class);
        $this->assertInstanceOf(CustomProducer::class, $this->pool->getDefaultProducer());
    }

    public function testUnknownClassCantBeSetAsDefaultProducer()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Producer class not found.");
        $this->pool->setDefaultProducerClass('Unknown class');
    }

    public function testDefaultProducerClassNeedsToImlementProducerInterface()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Producer class does not implement producer interface.");
        $this->pool->setDefaultProducerClass(Validator::class);
    }
}
