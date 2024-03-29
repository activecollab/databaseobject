<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Users\User;
use ActiveCollab\DatabaseObject\Validator;

class UrlValidatorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->pool->registerType(User::class);
        $this->assertTrue($this->pool->isTypeRegistered(User::class));
    }

    /**
     * Test if valid url address passes validation.
     */
    public function testValidUrlAddressPassesValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['homepage_url' => 'http://www.google.com']);

        $is_valid_url = $validator->url('homepage_url');

        $this->assertTrue($is_valid_url);
        $this->assertFalse($validator->hasErrors());

        $url_errors = $validator->getFieldErrors('homepage_url');

        $this->assertIsArray($url_errors);
        $this->assertCount(0, $url_errors);
    }

    public function testNoUrlFailsValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, []);

        $is_valid_url = $validator->url('homepage_url');

        $this->assertFalse($is_valid_url);
        $this->assertTrue($validator->hasErrors());

        $url_errors = $validator->getFieldErrors('homepage_url');

        $this->assertIsArray($url_errors);
        $this->assertCount(1, $url_errors);
        $this->assertContains("Value of 'homepage_url' is required.", $url_errors);
    }

    /**
     * Test if invalid url address does not pass url validation.
     */
    public function testInvalidUrlAddressFailsValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['homepage_url' => 'not valid url']);

        $is_valid_url = $validator->url('homepage_url');

        $this->assertFalse($is_valid_url);
        $this->assertTrue($validator->hasErrors());

        $url_errors = $validator->getFieldErrors('homepage_url');

        $this->assertIsArray($url_errors);
        $this->assertCount(1, $url_errors);
        $this->assertContains("Value of 'homepage_url' is not a valid URL.", $url_errors);
    }

    /**
     * Test if NULL value passes validation when it is allowed.
     */
    public function testNullPassesValidationWhenAllowed()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['homepage_url' => null]);

        $is_valid_url = $validator->url('homepage_url', true);

        $this->assertTrue($is_valid_url);
        $this->assertFalse($validator->hasErrors());

        $url_errors = $validator->getFieldErrors('homepage_url');

        $this->assertIsArray($url_errors);
        $this->assertCount(0, $url_errors);
    }

    /**
     * Test if NULL value fails validation when it is not allowed.
     */
    public function testNullFailsValidationWhenNotAllowed()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['homepage_url' => null]);

        $is_valid_url = $validator->url('homepage_url', false);

        $this->assertFalse($is_valid_url);
        $this->assertTrue($validator->hasErrors());

        $url_errors = $validator->getFieldErrors('homepage_url');

        $this->assertIsArray($url_errors);
        $this->assertCount(1, $url_errors);
        $this->assertContains("Value of 'homepage_url' is required.", $url_errors);
    }
}
