<?php

namespace ActiveCollab\DatabaseObject\Test;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class AutploaderTest extends TestCase
{
    /**
     * Test if classes are properly loaded
     */
    public function testAutoloader()
    {
        $this->assertTrue(class_exists('\ActiveCollab\DatabaseObject\Object'));
        $this->assertTrue(class_exists('\ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer'));
    }
}