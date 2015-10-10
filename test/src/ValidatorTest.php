<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;
use ActiveCollab\DatabaseObject\Validator;
use ActiveCollab\DatabaseObject\Exception\ValidationException;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class ValidatorTest extends TestCase
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

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateValue('1828-09-09'), 'Alexander Pushkin', new DateValue('1799-06-06'), 'Fyodor Dostoyevsky', new DateValue('1821-11-11'));

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
     * Test if validator properly produces and populates ValidationException
     */
    public function testValidatorException()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => 'Leo Tolstoy']);

        $is_unique = $validator->unique('name');

        $this->assertFalse($is_unique);
        $this->assertTrue($validator->hasErrors());

        $exception = $validator->createException();

        $this->assertInstanceOf(ValidationException::class, $exception);

        $this->assertTrue($exception->hasErrors());
        $this->assertTrue($exception->hasError('name'));
        $this->assertFalse($exception->hasError('unknown_column_here'));
    }
}