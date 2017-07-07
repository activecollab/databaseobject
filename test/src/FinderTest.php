<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Finder;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

final class FinderTest extends WritersTypeTestCase
{
    public function testToString()
    {
        $finder = new Finder($this->connection, $this->pool, $this->logger, Writer::class);

        $this->assertSame($finder->getSelectSql(), (string) $finder);
    }
}
