<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\FinderFactory\FinderFactory;
use ActiveCollab\DatabaseObject\FinderInterface;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

final class FinderFactoryTest extends WritersTypeTestCase
{
    public function testFinderFactoryProducesFinder()
    {
        $finder = (new FinderFactory(
            $this->connection,
            $this->pool,
            $this->logger
        ))->produceFinder(Writer::class);

        $this->assertInstanceOf(FinderInterface::class, $finder);
        $this->assertSame(Writer::class, $finder->getType());
    }

    public function testFinderFactoryCanSetWhere()
    {
        $finder = (new FinderFactory(
            $this->connection,
            $this->pool,
            $this->logger
        ))->produceFinder(Writer::class, '`name` = ?', 'Leo Tolstoy');

        $this->assertInstanceOf(FinderInterface::class, $finder);
        $this->assertSame(Writer::class, $finder->getType());

        $this->assertStringContainsString("`name` = 'Leo Tolstoy'", (string) $finder);
    }
}
