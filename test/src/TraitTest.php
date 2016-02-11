<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class TraitTest extends TestCase
{
    /**
     * Set up test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
    }

    /**
     * Test if trait "constructors" are called when instance is created.
     */
    public function testIfTraitsAreCalledWhenNewInstanceIsCreated()
    {
        $unknown_writer = new Writer($this->connection, $this->pool);

        $this->assertTrue($unknown_writer->is_russian);
        $this->assertTrue($unknown_writer->is_classic_writer);
    }
}
