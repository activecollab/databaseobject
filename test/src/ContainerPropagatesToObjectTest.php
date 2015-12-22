<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Container;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class ContainerPropagatesToObjectTest extends WritersTypeTestCase
{
    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->pool->setContainer(new Container([
            'is_special' => true
        ]));
        $this->assertTrue($this->pool->hasContainer());
        $this->assertInstanceOf(Container::class, $this->pool->getContainer());;
    }

    /**
     * Test instantination via getById() method
     */
    public function testGetByIdInstantination()
    {
        /** @var Writer $special_writer */
        $special_writer = $this->pool->getById(Writer::class, 1);

        $this->assertInstanceOf(Writer::class, $special_writer);
        $this->assertTrue($special_writer->hasContainer());
        $this->assertTrue($special_writer->is_special);
    }

    /**
     * Test instantination via finder
     */
    public function testFindIdInstantination()
    {
        /** @var Writer $special_writer */
        $special_writer = $this->pool->find(Writer::class)->where('id = ?', 1)->first();

        $this->assertInstanceOf(Writer::class, $special_writer);
        $this->assertTrue($special_writer->is_special);
    }

    /**
     * Test instantinaion using producer
     */
    public function testInstantinationUsingProducer()
    {
        /** @var Writer $special_writer */
        $special_writer = $this->pool->produce(Writer::class, [
            'name' => 'Special Writer',
            'birthday' => '2013-10-02',
        ]);

        $this->assertInstanceOf(Writer::class, $special_writer);
        $this->assertTrue($special_writer->is_special);
    }
}
