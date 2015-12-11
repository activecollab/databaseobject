<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Validator;
use ActiveCollab\DateValue\DateValue;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class UniqueValidatorTest extends WritersTypeTestCase
{
    /**
     * Test if theres no error thrown for NULL even though there are NULL records in the table
     */
    public function testNoErrorOnNull()
    {
        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (NULL, ?)', new DateValue('1828-09-09'));

        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) FROM `writers` WHERE `name` IS NULL'));

        $validator = new Validator($this->connection, 'writers', null, null, ['name' => null]);

        $is_unique = $validator->unique('name');

        $this->assertTrue($is_unique);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(0, $name_errors);
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

    /**
     * Test unique where filter does not throw an error when conditions are not met
     */
    public function testNoErrorWhenUniqueWhereIsNotMatched()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => 'Leo Tolstoy']);

        $is_unique = $validator->uniqueWhere('name', [ 'birthday < ?', new DateValue('1800-01-01') ]);

        $this->assertTrue($is_unique);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(0, $name_errors);
    }

    /**
     * Test unique where filter properly reports an error when conditions are met
     */
    public function testErrorWhenUniqueWhereIsMatched()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => 'Leo Tolstoy']);

        $is_unique = $validator->uniqueWhere('name', [ 'birthday < ?', new DateValue('1900-01-01') ]);

        $this->assertFalse($is_unique);
        $this->assertTrue($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(1, $name_errors);
    }
}
