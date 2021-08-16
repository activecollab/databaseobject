<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Finder;
use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Container;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

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

        $container = new Container(['dependency' => 'it works!']);

        $this->pool->setContainer($container);
        $this->assertTrue($this->pool->hasContainer());
        $this->assertInstanceOf(Container::class, $this->pool->getContainer());

        $this->pool->registerType(Writer::class);
    }

    /**
     * Test if container propages to finder.
     */
    public function testCustomFinderRecivesContainer()
    {
        /** @var Finder $finder */
        $finder = $this->pool->find(Writer::class);

        $this->assertInstanceOf(Container::class, $finder->getContainer());
        $this->assertTrue($finder->getContainer()->has('dependency'));
    }

    /**
     * Test custom finder instantination.
     */
    public function testCustomFinderInstantination()
    {
        /** @var Finder $finder */
        $finder = $this->pool->find(Writer::class);

        $this->assertInstanceOf(Finder::class, $finder);

        if (isset($finder->dependency)) {
            $this->assertEquals('it works!', $finder->dependency);
        } else {
            $this->fail('Dependency property not set in Finder');
        }
    }
}
