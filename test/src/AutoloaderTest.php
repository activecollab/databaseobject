<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\TestCase;

class AutoloaderTest extends TestCase
{
    public function testAutoloader()
    {
        $this->assertTrue(class_exists('\ActiveCollab\DatabaseObject\Entity\Entity'));
        $this->assertTrue(class_exists('\ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer'));
    }
}
