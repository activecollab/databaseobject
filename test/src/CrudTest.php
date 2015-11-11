<?php
namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\AwesomeWriter;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class CrudTest extends WritersTypeTestCase
{
    /**
     * Test if instances get default values pre-populated
     */
    public function testNewInstancesGetDefaultFieldValues()
    {
        $unknown_writer = new Writer($this->pool, $this->connection);
        $this->assertEquals('Unknown Writer', $unknown_writer->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFieldsWithDefaultValueCantBeNull()
    {
        $unknown_writer = new Writer($this->pool, $this->connection);
        $unknown_writer->setName(null);
    }

    /**
     * Test if ID is primary key
     */
    public function testIdIsPrimaryKey()
    {
        $unknown_writer = new Writer($this->pool, $this->connection);

        $this->assertTrue($unknown_writer->isPrimaryKey('id'));
        $this->assertFalse($unknown_writer->isPrimaryKey('name'));
    }

    /**
     * Test get object by ID
     */
    public function testGetById()
    {
        /** @var Writer $tolstoy */
        $tolstoy = $this->pool->getById(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());
        $this->assertSame(1, $tolstoy->getId());
        $this->assertSame('Leo Tolstoy', $tolstoy->getName());
        $this->assertSame('1828-09-09', $tolstoy->getBirthday()->format('Y-m-d'));
    }

    /**
     * Test if Pool::getById() properly returns NULL when record was not found
     */
    public function testGetByIdForNonExistingRecord()
    {
        $this->assertFalse($this->pool->exists(Writer::class, 890));
        $this->assertNull($this->pool->getById(Writer::class, 890));
    }

    /**
     * Test if Pool::mustGetById() throws an exception when record was not found
     *
     * @expectedException \ActiveCollab\DatabaseObject\Exception\ObjectNotFoundException
     */
    public function testMustGetByIdThrowsAnException()
    {
        $this->assertFalse($this->pool->exists(Writer::class, 890));
        $this->assertNull($this->pool->mustGetById(Writer::class, 890));
    }

    /**
     * Test if getById is subclassing aware
     */
    public function testSublassingAwareGetById()
    {
        /** @var Writer $tolstoy */
        $tolstoy = $this->pool->getById(AwesomeWriter::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());
        $this->assertSame(1, $tolstoy->getId());
        $this->assertSame('Leo Tolstoy', $tolstoy->getName());
        $this->assertSame('1828-09-09', $tolstoy->getBirthday()->format('Y-m-d'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetByIdThrowsAnExceptionOnUnregisteredType()
    {
        $this->pool->getById(DateValue::class, 1);
    }

    /**
     * Object create
     */
    public function testCreate()
    {
        $chekhov = new Writer($this->pool, $this->connection);

        $chekhov->setName('Anton Chekhov');
        $chekhov->setBirthday(new DateValue('1860-01-29'));

        $chekhov->save();

        $this->assertTrue($chekhov->isLoaded());
        $this->assertSame(4, $chekhov->getId());
        $this->assertSame('Anton Chekhov', $chekhov->getName());
        $this->assertEquals('1860-01-29', $chekhov->getBirthday()->format('Y-m-d'));
    }

    /**
     * Test record update
     */
    public function testUpdate()
    {
        /** @var Writer $tolstoy */
        $tolstoy = $this->pool->getById(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());

        $tolstoy->setName('Lev Nikolayevich Tolstoy');
        $tolstoy->save();

        $tolstoy = $this->pool->reload(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());

        $this->assertEquals('Lev Nikolayevich Tolstoy', $tolstoy->getName());
    }

    /**
     * Test if we can change ID to a new value that is not yet reserved
     */
    public function testChangeIdToNewRecord()
    {
        $chekhov = new Writer($this->pool, $this->connection);

        $chekhov->setName('Anton Chekhov');
        $chekhov->setBirthday(new DateValue('1860-01-29'));

        $chekhov->save();

        $this->assertFalse($chekhov->isPrimaryKeyModified());

        $this->assertSame(4, $chekhov->getId());

        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 4));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 18));

        // Update primary key and save the object
        $chekhov->setId(18);
        $this->assertTrue($chekhov->isPrimaryKeyModified());

        $chekhov->save();

        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 4));
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 18));
    }

    /**
     * @expectedException \LogicException
     */
    public function testChangeIdToExistingRecord()
    {
        $chekhov = new Writer($this->pool, $this->connection);

        $chekhov->setName('Anton Chekhov');
        $chekhov->setBirthday(new DateValue('1860-01-29'));

        $chekhov->save();

        $this->assertSame(4, $chekhov->getId());

        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 1));
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `id` = ?', 4));

        $chekhov->setId(1);
        $chekhov->save();
    }

    /**
     * Test delete
     */
    public function testDelete()
    {
        $this->assertTrue($this->pool->exists(Writer::class, 1));

        /** @var Writer $tolstoy */
        $tolstoy = $this->pool->getById(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $tolstoy);
        $this->assertTrue($tolstoy->isLoaded());

        $tolstoy->delete();

        $this->assertFalse($this->pool->exists(Writer::class, 1));
    }

    /**
     * Test set attributes when we have on_set_attribute event handler specified
     */
    public function testSetAttributeWithEventHandler()
    {
        $writer = new Writer($this->pool, $this->connection);
        $this->assertNull($writer->custom_attribute_value);
        $writer->setAttribute('custom_attribute', 13.5);
        $this->assertSame(13.5, $writer->custom_attribute_value);
    }

    /**
     * Test set attributes when we find attribute setter
     */
    public function testSetAttributeWithSetter()
    {
        $writer = new Writer($this->pool, $this->connection);
        $this->assertNull($writer->getCustomFieldValue());
        $writer->setAttribute('custom_field_value', 12);
        $this->assertSame(12, $writer->getCustomFieldValue());
    }

    /**
     * Test that there will be no exception if we try to set an unknown attribute
     */
    public function testUnknownAttributeDoesNotProduceAnError()
    {
        $writer = new Writer($this->pool, $this->connection);
        $writer->setAttribute('unknown_attribute', 12);
    }
}