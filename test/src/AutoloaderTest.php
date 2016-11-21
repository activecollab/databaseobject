<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\TestCase;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class AutoloaderTest extends TestCase
{
    /**
     * Test if classes are properly loaded.
     */
    public function testAutoloader()
    {
        $this->assertTrue(class_exists('\ActiveCollab\DatabaseObject\Entity\Entity'));
        $this->assertTrue(class_exists('\ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer'));
    }
}
