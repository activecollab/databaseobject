<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\ContainerAccess\ContainerAccessInterface\Implementation;
use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\ClassicWriter;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\Russian;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseObject\TraitsResolver\TraitsResolver;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class TraitTest extends TestCase
{
    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
    }

    public function testClassTraits()
    {
        $trait_names = (new TraitsResolver())->getClassTraits(Writer::class);

        $this->assertIsArray($trait_names);
        $this->assertCount(3, $trait_names);

        $this->assertContains(Implementation::class, $trait_names);
        $this->assertContains(Russian::class, $trait_names);
        $this->assertContains(ClassicWriter::class, $trait_names);
    }

    /**
     * Test if trait "constructors" are called when instance is created.
     */
    public function testIfTraitsAreCalledWhenNewInstanceIsCreated()
    {
        $unknown_writer = new Writer($this->connection, $this->pool, $this->logger);

        $this->assertTrue($unknown_writer->is_russian);
        $this->assertTrue($unknown_writer->is_classic_writer);
    }
}
