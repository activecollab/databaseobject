<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use Psr\Container\ContainerInterface;

class ContainerPropagatesToObjectTest extends WritersTypeTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with('is_special')
            ->willReturn(true);

        $this->pool->setContainer($container);
    }

    /**
     * Test instantiation via getById() method.
     */
    public function testGetByIdInstantination()
    {
        $special_writer = $this->pool->getById(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $special_writer);
        $this->assertTrue($special_writer->hasContainer());
        $this->assertTrue($special_writer->is_special);
    }

    public function testGetFirstByInstantination()
    {
        /** @var Writer $special_writer */
        $special_writer = $this->pool->getFirstBy(Writer::class, '`name` = ?', 'Leo Tolstoy');

        $this->assertInstanceOf(Writer::class, $special_writer);
        $this->assertTrue($special_writer->hasContainer());
        $this->assertTrue($special_writer->is_special);
    }

    /**
     * Test instantination via finder.
     */
    public function testFindIdInstantination()
    {
        /** @var Writer $special_writer */
        $special_writer = $this->pool->find(Writer::class)->where('id = ?', 1)->first();

        $this->assertInstanceOf(Writer::class, $special_writer);
        $this->assertTrue($special_writer->is_special);
    }

    /**
     * Test instantinaion using producer.
     */
    public function testInstantinationUsingProducer()
    {
        /** @var Writer $special_writer */
        $special_writer = $this->pool->produce(
            Writer::class,
            [
                'name' => 'Special Writer',
                'birthday' => '2013-10-02',
            ]
        );

        $this->assertInstanceOf(Writer::class, $special_writer);
        $this->assertTrue($special_writer->is_special);
    }

    public function testFindBySql()
    {
        /** @var Writer[] $writers */
        $writers = $this->pool->findBySql(Writer::class, 'SELECT * FROM `writers` WHERE `id` = ?', 1);

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(1, $writers);

        $special_writer = $writers[0];

        $this->assertInstanceOf(Writer::class, $special_writer);
        $this->assertTrue($special_writer->is_special);
    }
}
