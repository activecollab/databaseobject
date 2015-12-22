<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\CustomFinder\CustomFinderFinder;
use ActiveCollab\DatabaseObject\Test\Fixtures\CustomFinder\CustomFinderPool;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class CustomFinderTest extends TestCase
{
    /**
     * Test custom finder instantination
     */
    public function testCustomFinderInstantination()
    {
        $pool = new CustomFinderPool($this->connection);
        $pool->registerType(Writer::class);

        /** @var CustomFinderFinder $finder */
        $finder = $pool->find(Writer::class);

        $this->assertInstanceOf(CustomFinderFinder::class, $finder);
        $this->assertEquals('it works!', $finder->getDependency());
    }
}
