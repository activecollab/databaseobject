<?php
namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\ContainerAccessInterface\Implementation;
use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Users\User;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\AwesomeWriter;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\Russian;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\ClassicWriter;
use ActiveCollab\DatabaseObject\Validator;
use ActiveCollab\DateValue\DateValue;;

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
        $this->pool->registerType(DateValue::class);
    }

    /**
     * Test type registration
     */
    public function testRegisterType()
    {
        $this->assertFalse($this->pool->isTypeRegistered(Writer::class));
        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
        $this->assertTrue($this->pool->isTypeRegistered('\\' . Writer::class));
    }

    /**
     * Test if type registration is aware of subclassing
     */
    public function testRegisteredTypeIsSubclassingAware()
    {
        $this->pool->registerType(Writer::class);

        $this->assertEquals(Writer::class, $this->pool->getRegisteredType(Writer::class));
        $this->assertEquals(Writer::class, $this->pool->getRegisteredType(AwesomeWriter::class));
        $this->assertNull($this->pool->getRegisteredType(Validator::class));
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
     * Test table name for class that extends registered type
     */
    public function testTableNameForClassThatExtendsRegisteredType()
    {
        $this->pool->registerType(Writer::class);
        $this->assertEquals('writers', $this->pool->getTypeTable(AwesomeWriter::class));
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

    /**
     * Test if we can get fields for class that extends registered type
     */
    public function testFieldsForClassThatExtendsRegisteredType()
    {
        $this->pool->registerType(Writer::class);

        $fileds = $this->pool->getTypeFields(AwesomeWriter::class);

        $this->assertInternalType('array', $fileds);

        $this->assertContains('id', $fileds);
        $this->assertContains('name', $fileds);
        $this->assertContains('birthday', $fileds);
    }

    /**
     * Test is type polymorph check
     */
    public function testIsTypePolymorph()
    {
        $this->pool->registerType(Writer::class);
        $this->pool->registerType(User::class);

        $this->assertFalse($this->pool->isTypePolymorph(Writer::class));
        $this->assertFalse($this->pool->isTypePolymorph(AwesomeWriter::class));
        $this->assertTrue($this->pool->isTypePolymorph(User::class));
    }

    /**
     * Test order by for registered type
     */
    public function testOrderByForRegisteredType()
    {
        $this->pool->registerType(Writer::class);
        $this->assertEquals(['!id'], $this->pool->getTypeOrderBy(Writer::class));
        $this->assertEquals('`writers`.`id` DESC', $this->pool->getEscapedTypeOrderBy(Writer::class));
    }

    /**
     * Test order by for class that extends registered type
     */
    public function testOrderByForClassThatExtendsRegisteredType()
    {
        $this->pool->registerType(Writer::class);
        $this->assertEquals(['!id'], $this->pool->getTypeOrderBy(AwesomeWriter::class));
        $this->assertEquals('`writers`.`id` DESC', $this->pool->getEscapedTypeOrderBy(AwesomeWriter::class));
    }

    /**
     * Test return type property
     */
    public function testGetTypeProperty()
    {
        $this->pool->registerType(Writer::class);

        $this->assertEquals(1, $this->pool->getTypeProperty(Writer::class, 'should_be_one', function() {
            return 1;
        }));

        $this->assertEquals(1, $this->pool->getTypeProperty(Writer::class, 'should_be_one', function() {
            return 2345; // This callback should never be executed because we already have the value set in previous call
        }));
    }

    /**
     * Test if type properties are set for registered type, even when called for subtypes
     */
    public function testGetTypePropertySetsPropetyForTheRegisteredType()
    {
        $this->pool->registerType(Writer::class);

        $this->assertEquals(123, $this->pool->getTypeProperty(AwesomeWriter::class, 'should_be_set_for_writer', function() {
            return 123;
        }));

        $this->assertEquals(123, $this->pool->getTypeProperty(Writer::class, 'should_be_set_for_writer', function() {
            return 987;
        }));
    }

    /**
     * Get trait names by type
     */
    public function testTraitNamesByType()
    {
        $trait_names = $this->pool->getTraitNamesByType(Writer::class);

        $this->assertInternalType('array', $trait_names);
        $this->assertCount(3, $trait_names);

        $this->assertContains(Implementation::class, $trait_names);
        $this->assertContains(Russian::class, $trait_names);
        $this->assertContains(ClassicWriter::class, $trait_names);
    }
}