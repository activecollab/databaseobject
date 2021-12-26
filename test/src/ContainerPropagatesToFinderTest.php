<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Finder;
use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use Psr\Container\ContainerInterface;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class ContainerPropagatesToFinderTest extends TestCase
{
    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

//        $container = new Container(['dependency' => 'it works!']);
//
//        $this->pool->setContainer($container);
//        $this->assertTrue($this->pool->hasContainer());
//        $this->assertInstanceOf(Container::class, $this->pool->getContainer());

        $this->pool->registerType(Writer::class);
    }

    /**
     * Test if container propages to finder.
     */
    public function testCustomFinderRecivesContainer()
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
