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
}
