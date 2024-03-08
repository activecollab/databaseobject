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

class InArrayValidatorTest extends TestCase
{
    private array $continents = [
        'Asia',
        'Africa',
        'North America',
        'South America',
        'Antarctica',
        'Europe',
        'Australia',
    ];

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
     * Test if valid array value passes validation.
     */
    public function testValueInArrayPassesValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['continent' => 'Australia']);

        $is_valid_continent = $validator->inArray('continent', $this->continents);

        $this->assertTrue($is_valid_continent);
        $this->assertFalse($validator->hasErrors());

        $continent_errors = $validator->getFieldErrors('continent');

        $this->assertIsArray($continent_errors);
        $this->assertCount(0, $continent_errors);
    }

    /**
     * Test if invalid array value does not pass in array validation.
     */
    public function testValueNotInArrayFailsValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['continent' => 'Mars']);

        $is_valid_continent = $validator->inArray('continent', $this->continents);

        $this->assertFalse($is_valid_continent);
        $this->assertTrue($validator->hasErrors());

        $continent_errors = $validator->getFieldErrors('continent');

        $this->assertIsArray($continent_errors);
        $this->assertCount(1, $continent_errors);
    }

    /**
     * Test if NULL value passes validation when it is allowed.
     */
    public function testNullPassesValidationWhenAllowed()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['continent' => null]);

        $is_valid_continent = $validator->inArray('continent', $this->continents, true);

        $this->assertTrue($is_valid_continent);
        $this->assertFalse($validator->hasErrors());

        $continent_errors = $validator->getFieldErrors('continent');

        $this->assertIsArray($continent_errors);
        $this->assertCount(0, $continent_errors);
    }

    /**
     * Test if NULL value fails validation when it is not allowed.
     */
    public function testNullFailsValidationWhenNotAllowed()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['continent' => null]);

        $is_valid_continent = $validator->inArray('continent', $this->continents);

        $this->assertFalse($is_valid_continent);
        $this->assertTrue($validator->hasErrors());

        $continent_errors = $validator->getFieldErrors('continent');

        $this->assertIsArray($continent_errors);
        $this->assertCount(1, $continent_errors);
    }
}
