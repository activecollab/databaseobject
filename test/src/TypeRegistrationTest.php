<?php
namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use DateTime;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class TypeRegistrationTest extends TestCase
{
    /**
     * Test if there is no types defined by default
     */
    public function testRegisteredTypesIsEmptyByDefault()
    {
        $this->assertSame([], $this->pool->getRegisteredTypes());
    }

    /**
     * Test if registerType() can be called without types
     */
    public function testRegisterEmptyList()
    {
        $this->pool->registerType();
        $this->assertSame([], $this->pool->getRegisteredTypes());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionWhenRegisteringInvalidClass()
    {
        $this->pool->registerType(DateTime::class);
    }

    /**
     * Test type registration
     */
    public function testRegisterType()
    {
        $this->assertFalse($this->pool->isTypeRegistered(Writer::class));
        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
    }

    /**
     * Test table name for registered type
     */
    public function testTableNameForRegisteredType()
    {
        $this->pool->registerType(Writer::class);
        $this->assertEquals('writers', $this->pool->getTypeTable(Writer::class));
        $this->assertEquals($this->connection->escapeTableName('writers'), $this->pool->getTypeTable(Writer::class, true));
    }

    /**
     * Test fields for registered type
     */
    public function testFieldsForRegisteredTypes()
    {
        $this->pool->registerType(Writer::class);

        $fileds = $this->pool->getTypeFields(Writer::class);

        $this->assertInternalType('array', $fileds);

        $this->assertContains('id', $fileds);
        $this->assertContains('name', $fileds);
        $this->assertContains('birthday', $fileds);
    }
}