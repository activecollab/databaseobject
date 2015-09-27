<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseObject\Validator;
use DateTime;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class UniqueValidatorTest extends TestCase
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
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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

    /**
     * Test new record does not throw an error when name value is not a duplicate
     */
    public function testNewRecordDoesNotReportAnErrorWhenTheresNoDuplicateValue()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => 'Anton Chekhov']);

        $is_unique = $validator->unique('name');

        $this->assertTrue($is_unique);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(0, $name_errors);
    }

    /**
     * Test if validate unique value works properly
     */
    public function testNewRecordReportsAnErrorOnDuplicateValue()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => 'Leo Tolstoy']);

        $is_unique = $validator->unique('name');

        $this->assertFalse($is_unique);
        $this->assertTrue($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(1, $name_errors);
    }

    /**
     * Test if error will not be thrown for existing row that does not produces a duplicate value
     */
    public function testExistingRecordDoesNotReportAnErrorWhenTheresNoDuplicateValue()
    {
        $validator = new Validator($this->connection, 'writers', 1, null, ['name' => 'Leo Tolstoy']);

        $is_unique = $validator->unique('name');

        $this->assertTrue($is_unique);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(0, $name_errors);
    }

    /**
     * Test if error will be thrown for existing row that does produces a duplicate value
     */
    public function testExistingRecordReportsAnErrorOnDuplicateValue()
    {
        $validator = new Validator($this->connection, 'writers', 1, null, ['name' => 'Alexander Pushkin']);

        $is_unique = $validator->unique('name');

        $this->assertFalse($is_unique);
        $this->assertTrue($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(1, $name_errors);
    }

    /**
     * Test if error will not be thrown for existing row that does not produces a duplicate value, but changes ID
     */
    public function testExistingRecordDoesNotReportAnErrorWhenTheresNoDuplicateValueOnIdChange()
    {
        $validator = new Validator($this->connection, 'writers', 8, 1, ['name' => 'Leo Tolstoy']);

        $is_unique = $validator->unique('name');

        $this->assertTrue($is_unique);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(0, $name_errors);
    }

    /**
     * Test if error will be thrown for existing row that does produces a duplicate value, but changes ID
     */
    public function testExistingRecordReportsAnErrorOnDuplicateValueOnIdChange()
    {
        $validator = new Validator($this->connection, 'writers', 8, 1, ['name' => 'Alexander Pushkin']);

        $is_unique = $validator->unique('name');

        $this->assertFalse($is_unique);
        $this->assertTrue($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(1, $name_errors);
    }
}