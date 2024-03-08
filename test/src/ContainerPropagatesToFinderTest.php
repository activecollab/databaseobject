<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Finder;
use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use Psr\Container\ContainerInterface;

class ContainerPropagatesToFinderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->pool->registerType(Writer::class);
    }

    /**
     * Test if container propagates to finder.
     */
    public function testCustomFinderReceivesContainer()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('dependency')
            ->willReturn(true);

        $this->pool->setContainer($container);

        /** @var Finder $finder */
        $finder = $this->pool->find(Writer::class);

        $this->assertTrue($finder->getContainer()->has('dependency'));
    }

    /**
     * Test custom finder instantination.
     */
    public function testCustomFinderInstantination()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('dependency')
            ->willReturn(true);
        $container
            ->expects($this->once())
            ->method('get')
            ->with('dependency')
            ->willReturn('it works!');

        $this->pool->setContainer($container);

        /** @var Finder $finder */
        $finder = $this->pool->find(Writer::class);

        $this->assertInstanceOf(Finder::class, $finder);

        $this->assertTrue(isset($finder->dependency));
        $this->assertEquals('it works!', $finder->dependency);
    }
}
