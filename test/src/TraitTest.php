<?php
namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class TraitTest extends TestCase
{
    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
    }

    /**
     * Test if trait "constructors" are called when instance is created
     */
    public function testIfTraitsAreCalledWhenNewInstanceIsCreated()
    {
        $unknown_writer = new Writer($this->pool, $this->connection);

        $this->assertTrue($unknown_writer->is_russian);
        $this->assertTrue($unknown_writer->is_classic_writer);
    }
}