<?php
namespace ActiveCollab\DatabaseObject\Test;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class EnvironmentTest extends TestCase
{
    /**
     * Test if autoloader properly finds DatabaseObject files
     */
    public function testAutoloader()
    {
        $this->assertTrue(class_exists('ActiveCollab\\DatabaseObject\\Object'));
    }
}