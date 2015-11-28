<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Collection as WritersCollection;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

/**
 * Test data object collection
 *
 * @package angie.tests
 */
class TypeCollectionTest extends WritersTypeTestCase
{
    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        if ($this->connection->tableExists('favorite_writers')) {
            $this->connection->dropTable('favorite_writers');
        }

        parent::tearDown();
    }

    /**
     * Test if collection reads settings from type
     */
    function testTypeCollectionReadsSettingsFromType()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $this->assertEquals($collection->getTableName(), 'writers');
        $this->assertEquals($collection->getTimestampField(), 'updated_at');
        $this->assertEquals($collection->getOrderBy(), '`writers`.`id` DESC');
    }

    /**
     * Test set conditions from string
     */
    public function testSetConditionsFromString()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $collection->where('type = "File"');
        $this->assertEquals($collection->getConditions(), 'type = "File"');
    }

    /**
     * Test set conditions from array
     */
    public function testSetConditionsFromArray()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $collection->where(['type = ?', 'File']);
        $this->assertEquals($collection->getConditions(), "type = 'File'");
    }

    /**
     * Test set conditions from array of arguments
     */
    public function testSetConditionsFromArrayOfArguments()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $collection->where('type = ?', 'File');
        $this->assertEquals($collection->getConditions(), "type = 'File'");
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyListOfArgumentsThrowsAnException()
    {
        (new WritersCollection($this->connection, $this->pool))->where();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConditionsTypeThrowsAnException()
    {
        (new WritersCollection($this->connection, $this->pool))->where(123);
    }

    /**
     * Test set order by
     */
    public function testSetOrderBy()
    {
        $collection = new WritersCollection($this->connection, $this->pool);
        $collection->orderBy('`writers`.`created_at` DESC');

        $this->assertEquals($collection->getOrderBy(), '`writers`.`created_at` DESC');
    }

    /**
     * @expectedException \LogicException
     */
    public function testCoundShouldNotWorkWhenCollectionIsNotReady()
    {
        (new WritersCollection($this->connection, $this->pool))->setAsNotReady()->executeIds();
    }

    /**
     * @expectedException \LogicException
     */
    public function testExecuteShouldNotWorkWhenCollectionIsNotReady()
    {
        (new WritersCollection($this->connection, $this->pool))->setAsNotReady()->execute();
    }

    /**
     * @expectedException \LogicException
     */
    public function testExecuteIdsShouldNotWorkWhenCollectionIsNotReady()
    {
        (new WritersCollection($this->connection, $this->pool))->setAsNotReady()->executeIds();
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetTagShouldNotWorkWhenCollectionIsNotReady()
    {
        (new WritersCollection($this->connection, $this->pool))->setAsNotReady()->getEtag('ilija.studen@activecollab.com');
    }

    /**
     * Test if execute IDs returns a correct result
     */
    public function testExecuteIds()
    {
        $this->assertEquals([3, 2, 1], (new WritersCollection($this->connection, $this->pool))->executeIds());
    }

    /**
     * Test execute collection
     */
    public function testExecute()
    {
        $writers = (new WritersCollection($this->connection, $this->pool))->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(3, $writers);

        $this->assertEquals('Fyodor Dostoyevsky', $writers[0]->getName());
        $this->assertEquals('Alexander Pushkin', $writers[1]->getName());
        $this->assertEquals('Leo Tolstoy', $writers[2]->getName());
    }

    /**
     * Test execution with conditions
     */
    public function testExecuteWithConditions()
    {
        $writers = (new WritersCollection($this->connection, $this->pool))->where('`name` LIKE ? OR `name` LIKE ?', 'Fyodor%', 'Alexander%')->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(2, $writers);

        $this->assertEquals('Fyodor Dostoyevsky', $writers[0]->getName());
        $this->assertEquals('Alexander Pushkin', $writers[1]->getName());
    }

    /**
     * Test execution with conditions and order by set
     */
    public function testExecuteWithConditionsAndOrder()
    {
        $writers = (new WritersCollection($this->connection, $this->pool))->where('`name` LIKE ? OR `name` LIKE ?', 'Fyodor%', 'Alexander%')->orderBy('`name`')->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(2, $writers);

        $this->assertEquals('Alexander Pushkin', $writers[0]->getName());
        $this->assertEquals('Fyodor Dostoyevsky', $writers[1]->getName());
    }

    /**
     * Test is pagianted call
     */
    public function testIsPaginated()
    {
        $not_paginated = new WritersCollection($this->connection, $this->pool);
        $this->assertFalse($not_paginated->isPaginated());

        $paginated = (new WritersCollection($this->connection, $this->pool))->pagination(1, 2);
        $this->assertTrue($paginated->isPaginated());
    }

    /**
     * @expectedException \LogicException
     */
    public function testSetCurrentPageForNonPaginatedCollectionThrowsAnError()
    {
        (new WritersCollection($this->connection, $this->pool))->currentPage(1);
    }

    /**
     * Test if we can change current page for paginated collection
     */
    public function testSetCurrentPageForPaginatedCollection()
    {
        $paginated = (new WritersCollection($this->connection, $this->pool))->pagination(1, 2);
        $this->assertEquals(1, $paginated->getCurrentPage());

        $paginated->currentPage(18);
        $this->assertEquals(18, $paginated->getCurrentPage());
    }

    /**
     * Test paginated execution
     */
    public function testExecutePaginated()
    {
        //  Page 1
        $collection = (new WritersCollection($this->connection, $this->pool))->pagination(1, 2);

        $this->assertEquals(1, $collection->getCurrentPage());
        $this->assertEquals(2, $collection->getItemsPerPage());
        $this->assertEquals(3, $collection->count());

        $writers = $collection->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(2, $writers);

        $this->assertEquals('Fyodor Dostoyevsky', $writers[0]->getName());
        $this->assertEquals('Alexander Pushkin', $writers[1]->getName());

        //  Page 2
        $collection = (new WritersCollection($this->connection, $this->pool))->pagination(2, 2);

        $this->assertEquals(2, $collection->getCurrentPage());
        $this->assertEquals(2, $collection->getItemsPerPage());
        $this->assertEquals(3, $collection->count());

        $writers = $collection->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(1, $writers);

        $this->assertEquals('Leo Tolstoy', $writers[0]->getName());
    }

    /**
     * Confirm that join is turned off by default
     */
    public function testJoinIsTurnedOffByDefault()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $this->assertNull($collection->getJoinTable());
        $this->assertNull($collection->getJoinField());
    }

    /**
     * Test if join field is set based on table name
     */
    public function testJoinFieldBasedOnTableName()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $collection->setJoinTable('writes_books');

        $this->assertEquals('writes_books', $collection->getJoinTable());
        $this->assertEquals('writer_id', $collection->getJoinField());
    }

    /**
     * Test if join field can be specified
     */
    public function testJoinFieldSpecified()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $collection->setJoinTable('writes_books', 'awesome_writer_id');

        $this->assertEquals('writes_books', $collection->getJoinTable());
        $this->assertEquals('awesome_writer_id', $collection->getJoinField());
    }

    /**
     * Test if we can set conditions so they are joined with another table
     */
    public function testJoin() 
    {
        $create_table = $this->connection->execute("CREATE TABLE `favorite_writers` (
            `user_id` int UNSIGNED NOT NULL DEFAULT '0',
            `writer_id` int UNSIGNED NOT NULL DEFAULT '0',
            PRIMARY KEY (`user_id`, `writer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        // Peter is user #1 and he likes Tolstoy and Dostoyevsky (#1 and #3)
        // John is user #2 and he likes only Pushkin (#2
        // Oliver is user #3 and he is not into Russian classics
        $this->connection->execute('INSERT INTO `favorite_writers` (`user_id`, `writer_id`) VALUES (1, 1), (1, 3), (2, 2)');

        $this->assertEquals(3, $this->connection->count('favorite_writers', null, '*'));

        // Peter's favorite writers
        $collection = (new WritersCollection($this->connection, $this->pool))->setJoinTable('favorite_writers')->where('`favorite_writers`.`user_id` = ?', 1)->orderBy('`name`');

        $this->assertEquals('favorite_writers', $collection->getJoinTable());
        $this->assertEquals('writer_id', $collection->getJoinField());

        $writers = $collection->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(2, $writers);

        $this->assertEquals('Fyodor Dostoyevsky', $writers[0]->getName());
        $this->assertEquals('Leo Tolstoy', $writers[1]->getName());

        // John's favorite writers
        $writers = (new WritersCollection($this->connection, $this->pool))->setJoinTable('favorite_writers')->where('`favorite_writers`.`user_id` = ?', 2)->orderBy('`name`')->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(1, $writers);

        $this->assertEquals('Alexander Pushkin', $writers[0]->getName());

        // Oliver's favorite writes
        $writers = (new WritersCollection($this->connection, $this->pool))->setJoinTable('favorite_writers')->where('`favorite_writers`.`user_id` = ?', 3)->orderBy('`name`')->execute();

        $this->assertNull($writers);
    }

    /**
     * Test if timestamp hash is properly loaded from the timestamp fields
     */
    public function testTimestampHash()
    {
        $this->connection->execute('UPDATE `writers` SET `updated_at` = ? WHERE `id` = ?', date('Y-m-d H:i:s', time() + 1), 2);
        $this->connection->execute('UPDATE `writers` SET `updated_at` = ? WHERE `id` = ?', date('Y-m-d H:i:s', time() + 2), 3);

        $collection = (new WritersCollection($this->connection, $this->pool))->orderBy('`writers`.`id`');

        $updated_on_timestamps = [];

        /** @var Writer $writer */
        foreach ($collection->execute() as $writer) {
            $updated_on_timestamps[] = $writer->getUpdatedAt()->format('Y-m-d H:i:s');
        }

        $this->assertCount(3, $updated_on_timestamps);
        $this->assertCount(3, array_unique($updated_on_timestamps));

        $this->assertEquals($collection->getTimestampHash('updated_at'), sha1(implode(',', $updated_on_timestamps)));
    }

    /**
     * Test if etag format is properly set
     */
    public function testEtagFormat()
    {
        $collection = (new WritersCollection($this->connection, $this->pool))->setApplicationIdentifier('MyApp v1.0');

        $etag = $collection->getEtag('ilija.studen@activecollab.com');

        $this->assertNotEmpty($etag);

        $etag_bits = explode(',', $etag);

        $this->assertCount(6, $etag_bits);

        $this->assertEquals('MyApp v1.0', $etag_bits[0]);
        $this->assertEquals('collection', $etag_bits[1]);
        $this->assertEquals(WritersCollection::class, $etag_bits[2]);
        $this->assertEquals('na', $etag_bits[3]);
        $this->assertEquals('ilija.studen@activecollab.com', $etag_bits[4]);
        $this->assertEquals($collection->getTimestampHash('updated_at'), $etag_bits[5]);
    }

    /**
     * Test if additional identifier can be specified
     */
    public function testEtagCanIncludeAdditionalIdenfitier()
    {
        $collection = (new WritersCollection($this->connection, $this->pool))->setApplicationIdentifier('MyApp v1.0')->setAdditionalIdenfitifier('addidf');

        $etag = $collection->getEtag('ilija.studen@activecollab.com');

        $this->assertNotEmpty($etag);

        $etag_bits = explode(',', $etag);

        $this->assertCount(6, $etag_bits);

        $this->assertEquals('MyApp v1.0', $etag_bits[0]);
        $this->assertEquals('collection', $etag_bits[1]);
        $this->assertEquals(WritersCollection::class, $etag_bits[2]);
        $this->assertEquals('addidf', $etag_bits[3]);
        $this->assertEquals('ilija.studen@activecollab.com', $etag_bits[4]);
        $this->assertEquals($collection->getTimestampHash('updated_at'), $etag_bits[5]);
    }
}