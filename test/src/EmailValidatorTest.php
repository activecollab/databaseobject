<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Users\User;
use ActiveCollab\DatabaseObject\Validator;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class EmailValidatorTest extends TestCase
{
    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->pool->registerType(User::class);
        $this->assertTrue($this->pool->isTypeRegistered(User::class));
    }

    /**
     * Test if valid email address passes validation.
     */
    public function testValidEmailAddressPassesValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['email' => 'address@domain.com']);

        $is_valid_email = $validator->email('email');

        $this->assertTrue($is_valid_email);
        $this->assertFalse($validator->hasErrors());

        $email_errors = $validator->getFieldErrors('email');

        $this->assertIsArray($email_errors);
        $this->assertCount(0, $email_errors);
    }

    /**
     * Test if invalid email address does not pass email validation.
     */
    public function testInvalidEmailAddressFailsValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['email' => 'not valid email']);

        $is_valid_email = $validator->email('email');

        $this->assertFalse($is_valid_email);
        $this->assertTrue($validator->hasErrors());

        $email_errors = $validator->getFieldErrors('email');

        $this->assertIsArray($email_errors);
        $this->assertCount(1, $email_errors);
    }

    /**
     * Test if NULL value passes validation when it is allowed.
     */
    public function testNullPassesValidationWhenAllowed()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['email' => null]);

        $is_valid_email = $validator->email('email', true);

        $this->assertTrue($is_valid_email);
        $this->assertFalse($validator->hasErrors());

        $email_errors = $validator->getFieldErrors('email');

        $this->assertIsArray($email_errors);
        $this->assertCount(0, $email_errors);
    }

    /**
     * Test if NULL value fails validation when it is not allowed.
     */
    public function testNullFailsValidationWhenNotAllowed()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['email' => null]);

        $is_valid_email = $validator->email('email', false);

        $this->assertFalse($is_valid_email);
        $this->assertTrue($validator->hasErrors());

        $email_errors = $validator->getFieldErrors('email');

        $this->assertIsArray($email_errors);
        $this->assertCount(1, $email_errors);
    }
}
