<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseObject\Validator;
use DateTime;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class PresenceValidatorTest extends TestCase
{
    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        $create_table = $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci,
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateTime('1828-09-09'), 'Alexander Pushkin', new DateTime('1799-06-06'), 'Fyodor Dostoyevsky', new DateTime('1821-11-11'));

        $this->pool->registerType(Writer::class);
        $this->assertTrue($this->pool->isTypeRegistered(Writer::class));
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        parent::tearDown();
    }

    public function testPass()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => 'Is present!']);

        $is_present = $validator->present('name');

        $this->assertTrue($is_present);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(0, $name_errors);
    }

    public function testFail()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => '']);

        $is_present = $validator->present('name');

        $this->assertFalse($is_present);
        $this->assertTrue($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(1, $name_errors);
    }

    public function testFailBecauseTheresNoValue()
    {
        $validator = new Validator($this->connection, 'writers', null, null, []);

        $is_present = $validator->present('name');

        $this->assertFalse($is_present);
        $this->assertTrue($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(1, $name_errors);
    }

    public function testTypeCheck()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => '0']);

        $is_present = $validator->present('name');

        $this->assertTrue($is_present);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(0, $name_errors);
    }
}