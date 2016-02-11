<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseConnection\Result\Result;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Finder;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class FindTest extends WritersTypeTestCase
{
    /**
     * Test count all.
     */
    public function testCount()
    {
        $this->assertEquals(3, $this->pool->count(Writer::class));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCountThrowsAnExceptionOnUnregisteredType()
    {
        $this->pool->count(DateValue::class);
    }

    /**
     * Test count by the given conditions.
     */
    public function testCountWithConditions()
    {
        $this->assertEquals(2, $this->pool->count(Writer::class, ['birthday > ?', new DateValue('1800-01-01')]));
    }

    /**
     * Test if find() method returns Finder instance.
     */
    public function testFindReturnsFinder()
    {
        $finder = $this->pool->find(Writer::class);

        $this->assertInstanceOf(Finder::class, $finder);
        $this->assertEquals(Writer::class, $finder->getType());
    }

    /**
     * Test find all writers from the database.
     */
    public function testFindAll()
    {
        /** @var Result $result */
        $result = $this->pool->find(Writer::class)->all();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount(3, $result);

        foreach ($result as $writer) {
            $this->assertInstanceOf(Writer::class, $writer);
        }
    }

    /**
     * Test find first record.
     */
    public function testFindFirst()
    {
        /** @var Writer $should_be_pushkin */
        $should_be_pushkin = $this->pool->find(Writer::class)->orderBy('`birthday`')->first();

        $this->assertInstanceOf(Writer::class, $should_be_pushkin);
        $this->assertTrue($should_be_pushkin->isLoaded());
        $this->assertEquals('Alexander Pushkin', $should_be_pushkin->getName());
    }

    /**
     * Test find all ID-s.
     */
    public function testFindAllIds()
    {
        $ids = $this->pool->find(Writer::class)->ids();

        $this->assertInternalType('array', $ids);
        $this->assertCount(3, $ids);
    }

    /**
     * Test if ids() returns an empty array on empty result set.
     */
    public function testFindIdsAlwaysReturnsArray()
    {
        $ids = $this->pool->find(Writer::class)->where('id = ?', -1)->ids();

        $this->assertInternalType('array', $ids);
        $this->assertCount(0, $ids);
    }

    /**
     * Test count using finder object.
     */
    public function testCountUsingFinder()
    {
        $this->assertEquals(3, $this->pool->find(Writer::class)->count());
    }

    /**
     * Test find by conditions.
     */
    public function testFindByConditions()
    {
        /** @var Writer $should_be_leo */
        $should_be_leo = $this->pool->find(Writer::class)->where('`name` LIKE ?', '%Leo%')->first();

        $this->assertInstanceOf(Writer::class, $should_be_leo);
        $this->assertTrue($should_be_leo->isLoaded());
        $this->assertEquals('Leo Tolstoy', $should_be_leo->getName());
    }

    /**
     * Test find using multiple calls to where() method.
     */
    public function testFindByMultipleConditions()
    {
        $finder_1 = $this->pool->find(Writer::class)->where('`birthday` > ?', '1800-01-01');
        $this->assertEquals("`birthday` > '1800-01-01'", $finder_1->getWhere());

        /* @var Writer[] $should_be_fyodor */
        $should_be_fyodor_and_leo = $finder_1->all();

        $this->assertCount(2, $should_be_fyodor_and_leo);

        $finder_2 = $this->pool->find(Writer::class)->where('`birthday` > ?', '1800-01-01')->where('`birthday` < ?', '1825-01-01');
        $this->assertEquals("(`birthday` > '1800-01-01') AND (`birthday` < '1825-01-01')", $finder_2->getWhere());

        /** @var Writer[] $should_be_fyodor */
        $should_be_fyodor = $finder_2->all();

        $this->assertCount(1, $should_be_fyodor);

        $this->assertInstanceOf(Writer::class, $should_be_fyodor[0]);
        $this->assertTrue($should_be_fyodor[0]->isLoaded());
        $this->assertEquals('Fyodor Dostoyevsky', $should_be_fyodor[0]->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConditionsPatternNeedsToBeString()
    {
        $this->pool->find(Writer::class)->where(['`name` LIKE ?', '%Leo%']);
    }

    /**
     * Test count using finder object with conditions.
     */
    public function testCountUsingFinderByConditions()
    {
        $this->assertEquals(1, $this->pool->find(Writer::class)->where('`name` LIKE ?', '%Leo%')->count());
    }

    /**
     * Test find ID-s by conditions.
     */
    public function testFindIdsByCondition()
    {
        $ids = $this->pool->find(Writer::class)->where('`name` LIKE ?', '%Leo%')->ids();

        $this->assertInternalType('array', $ids);
        $this->assertCount(1, $ids);
        $this->assertSame(1, $ids[0]);
    }

    /**
     * Test limit and offset.
     */
    public function testOffset()
    {
        $result = $this->pool->find(Writer::class)->orderBy('`birthday`')->limit(1, 1)->all();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount(1, $result);

        /** @var Writer $should_be_fyodor */
        $should_be_fyodor = $result[0];

        $this->assertInstanceOf(Writer::class, $should_be_fyodor);
        $this->assertTrue($should_be_fyodor->isLoaded());
        $this->assertEquals('Fyodor Dostoyevsky', $should_be_fyodor->getName());
    }

    /**
     * Test if dependencies are properly set to hydrated objects so they are fully functional.
     */
    public function testHydratedObjectsAreFullyFunctional()
    {
        /* @var Writer $leo */
        $should_be_leo = $this->pool->find(Writer::class)->where('`name` = ?', 'Leo Tolstoy')->first();

        $this->assertInstanceOf(Writer::class, $should_be_leo);
        $this->assertTrue($should_be_leo->isLoaded());
        $this->assertEquals('Leo Tolstoy', $should_be_leo->getName());

        $should_be_leo->setName('Lev Nikolayevich Tolstoy');
        $should_be_leo->save();

        $should_be_leo = $this->pool->reload(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $should_be_leo);
        $this->assertTrue($should_be_leo->isLoaded());

        $this->assertEquals('Lev Nikolayevich Tolstoy', $should_be_leo->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindBySqlRequiresType()
    {
        $this->pool->findBySql('', 'SELECT * FROM `writers` ORDER BY `name`');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindBySqlRequiresRegisteredType()
    {
        $this->pool->findBySql(DateValue::class, 'SELECT * FROM `writers` ORDER BY `name`');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindBySqlRequiresSqlStatement()
    {
        $this->pool->findBySql(Writer::class, '');
    }

    /**
     * Test if find by SQL properly loads data.
     */
    public function testFindBySql()
    {
        /** @var Writer[] $writers */
        $writers = $this->pool->findBySql(Writer::class, 'SELECT * FROM `writers` ORDER BY `name`');

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(3, $writers);

        $this->assertEquals('Alexander Pushkin', $writers[0]->getName());
        $this->assertEquals('Fyodor Dostoyevsky', $writers[1]->getName());
        $this->assertEquals('Leo Tolstoy', $writers[2]->getName());
    }

    /**
     * Test if find by SQL properly accepts and escapes arguments.
     */
    public function testFindBySqlWithArguments()
    {
        /** @var Writer[] $writers */
        $writers = $this->pool->findBySql(Writer::class, 'SELECT * FROM `writers` WHERE `name` LIKE ? OR `name` LIKE ? ORDER BY `name`', 'Alexander%', 'Leo%');

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(2, $writers);

        $this->assertEquals('Alexander Pushkin', $writers[0]->getName());
        $this->assertEquals('Leo Tolstoy', $writers[1]->getName());
    }
}
