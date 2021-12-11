<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Exception\ValidationException;
use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Validator;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class ValidatorTest extends WritersTypeTestCase
{
    /**
     * Test if validator properly produces and populates ValidationException.
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

    public function testValidatorReturnsAllErrors()
    {
        $validator = new Validator($this->connection, 'writers', null, null, [
            'name' => 'Leo Tolstoy',
            'birthday' => null,
        ]);

        $validator->unique('name');
        $validator->present('birthday');

        $this->assertTrue($validator->hasErrors());

        $errors = $validator->getErrors();

        $this->assertIsArray($errors);
        $this->assertCount(2, $errors);

        $this->assertArrayHasKey('name', $errors);
        $this->assertIsArray($errors['name']);
        $this->assertCount(1, $errors['name']);
        $this->assertEquals("Value of 'name' needs to be unique.", $errors['name'][0]);

        $this->assertArrayHasKey('birthday', $errors);
        $this->assertIsArray($errors['birthday']);
        $this->assertCount(1, $errors['birthday']);
        $this->assertEquals("Value of 'birthday' is required", $errors['birthday'][0]);
    }
}
