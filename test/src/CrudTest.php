<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Exception\ObjectNotFoundException;
use ActiveCollab\DatabaseObject\Exception\ValidationException;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\AwesomeWriter;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DateValue\DateValue;
use InvalidArgumentException;
use LogicException;

class CrudTest extends WritersTypeTestCase
{
    /**
     * Test if instances get default values pre-populated.
     */
    public function testNewInstancesGetDefaultFieldValues()
    {
        $unknown_writer = new Writer($this->connection, $this->pool, $this->logger);
        $this->assertEquals('Unknown Writer', $unknown_writer->getName());
    }

    public function testFieldsWithDefaultValueCantBeNull()
    {
        $this->expectException(InvalidArgumentException::class);
        $unknown_writer = new Writer($this->connection, $this->pool, $this->logger);
        $unknown_writer->setName(null);
    }

    public function testCantLoadFromEmptyRow()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Database row expected");
        $writer = new Writer($this->connection, $this->pool, $this->logger);
        $writer->loadFromRow([]);
    }

    /**
     * Test if ID is primary key.
     */
    public function testIdIsPrimaryKey()
    {
        $unknown_writer = new Writer($this->connection, $this->pool, $this->logger);

        $this->assertSame('id', $unknown_writer->getPrimaryKey());
        $this->assertTrue($unknown_writer->isPrimaryKey('id'));
        $this->assertFalse($unknown_writer->isPrimaryKey('name'));
    }

    /**
     * Test get object by ID.
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

    public function testGetByIdOnInvalidIdException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("ID is expected to be a number larger than 0");
        $this->pool->getById(Writer::class, 0);
    }

    /**
     * Test if Pool::getById() properly returns NULL when record was not found.
     */
    public function testGetByIdForNonExistingRecord()
    {
        $this->assertFalse($this->pool->exists(Writer::class, 890));
        $this->assertNull($this->pool->getById(Writer::class, 890));
    }

    /**
     * Test if Pool::mustGetById() throws an exception when record was not found.
     */
    public function testMustGetByIdThrowsAnException()
    {
        $this->expectException(ObjectNotFoundException::class);
        $this->assertFalse($this->pool->exists(Writer::class, 890));
        $this->assertNull($this->pool->mustGetById(Writer::class, 890));
    }

    /**
     * Test if getById is subclassing aware.
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

    public function testGetByIdThrowsAnExceptionOnUnregisteredType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pool->getById(DateValue::class, 1);
    }

    /**
     * Object create.
     */
    public function testCreate()
    {
        $chekhov = new Writer($this->connection, $this->pool, $this->logger);

        $chekhov->setName('Anton Chekhov');
        $chekhov->setBirthday(new DateValue('1860-01-29'));

        $chekhov->save();

        $this->assertTrue($chekhov->isLoaded());
        $this->assertSame(4, $chekhov->getId());
        $this->assertSame('Anton Chekhov', $chekhov->getName());
        $this->assertEquals('1860-01-29', $chekhov->getBirthday()->format('Y-m-d'));
    }

    public function testExceptionOnInvalidCreate()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Value of 'birthday' is required");
        (new Writer($this->connection, $this->pool, $this->logger))
            ->setName('Anton Chekhov')
            ->save();
    }

    /**
     * Test record update.
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
     * Test get modifications grouped by field name.
     */
    public function testModifications()
    {
        /** @var Writer $tolstoy */
        $tolstoy = $this->pool->getById(Writer::class, 1);

        $this->assertIsArray($tolstoy->getModifications());
        $this->assertEmpty($tolstoy->getModifications());

        $tolstoy->setName('Lev Nikolayevich Tolstoy')->setBirthday(new DateValue('1828-09-10'));

        $this->assertIsArray($tolstoy->getModifications());
        $this->assertCount(2, $tolstoy->getModifications());

        $this->assertEquals('Leo Tolstoy', $tolstoy->getModifications()['name'][0]);
        $this->assertEquals('Lev Nikolayevich Tolstoy', $tolstoy->getModifications()['name'][1]);

        $this->assertEquals('1828-09-09', $tolstoy->getModifications()['birthday'][0]->format('Y-m-d'));
        $this->assertEquals('1828-09-10', $tolstoy->getModifications()['birthday'][1]->format('Y-m-d'));

        $tolstoy->save();

        $this->assertIsArray($tolstoy->getModifications());
        $this->assertEmpty($tolstoy->getModifications());
    }

    /**
     * Test if we can change ID to a new value that is not yet reserved.
     */
    public function testChangeIdToNewRecord()
    {
        $chekhov = new Writer($this->connection, $this->pool, $this->logger);

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

    public function testChangeIdToExistingRecord()
    {
        $this->expectException(LogicException::class);
        $chekhov = new Writer($this->connection, $this->pool, $this->logger);

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
     * Test delete.
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
     * Test set attributes when we have on_set_attribute event handler specified.
     */
    public function testSetAttributeWithEventHandler()
    {
        $writer = new Writer($this->connection, $this->pool, $this->logger);
        $this->assertNull($writer->custom_attribute_value);
        $writer->setAttribute('custom_attribute', 13.5);
        $this->assertSame(13.5, $writer->custom_attribute_value);
    }

    /**
     * Test set attributes when we find attribute setter.
     */
    public function testSetAttributeWithSetter()
    {
        $writer = new Writer($this->connection, $this->pool, $this->logger);
        $this->assertNull($writer->getCustomFieldValue());
        $writer->setAttribute('custom_field_value', 12);
        $this->assertSame(12, $writer->getCustomFieldValue());
    }

    /**
     * Test if set attribute ignores unknown attributes (value will not be set, even though we can resolve setter).
     */
    public function testSetAttributeIgnoresUnknownAttributes()
    {
        $writer = new Writer($this->connection, $this->pool, $this->logger);
        $this->assertEquals('protected', $writer->getProtectedCustomFieldValue());
        $writer->setAttribute('protected_custom_field_value', 12);
        $this->assertEquals('protected', $writer->getProtectedCustomFieldValue());
    }

    /**
     * Test that there will be no exception if we try to set an unknown attribute.
     */
    public function testUnknownAttributeDoesNotProduceAnError()
    {
        $writer = new Writer($this->connection, $this->pool, $this->logger);
        $writer->setAttribute('unknown_attribute', 12);
    }
}
