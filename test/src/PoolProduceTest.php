<?php
namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\AwesomeWriter;
use ActiveCollab\DatabaseObject\Validator;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class PoolProduceTest extends TestCase
{
    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
    }

    /**
     * Test if pool can produce an instance of registered type
     */
    public function testPoolCanProduceRegisteredType()
    {
        $object = $this->pool->produce(Writer::class);

        $this->assertInstanceOf(Writer::class, $object);
    }

    /**
     * Test if pool can produce a new instance of subclass of registered type
     */
    public function testPoolCanProduceSubclassOfRegisteredType()
    {
        $object = $this->pool->produce(AwesomeWriter::class);

        $this->assertInstanceOf(Writer::class, $object);
        $this->assertInstanceOf(AwesomeWriter::class, $object);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnUnknonwClass()
    {
        $this->pool->produce('Definitely not a Class');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnNonObjectClass()
    {
        $this->pool->produce(Validator::class);
    }
}