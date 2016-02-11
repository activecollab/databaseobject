<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\PreconfiguredCollection;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class TypePreconfiguredCollectionTest extends WritersTypeTestCase
{
    /**
     * Test if configure() method is called when collection is constructed.
     */
    public function testCollectionIsPreconfigured()
    {
        $collection = new PreconfiguredCollection($this->connection, $this->pool);

        $this->assertEquals("`name` LIKE 'A%'", $collection->getConditions());
        $this->assertEquals('`name`', $collection->getOrderBy());
    }

    /**
     * Test if pre-configured collection works as expected.
     */
    public function testExecutePreconfiguredCollection()
    {
        $writers = (new PreconfiguredCollection($this->connection, $this->pool))->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(1, $writers);
        $this->assertEquals('Alexander Pushkin', $writers[0]->getName());
    }
}
