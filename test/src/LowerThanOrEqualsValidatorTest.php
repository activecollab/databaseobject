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
use InvalidArgumentException;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class LowerThanOrEqualsValidatorTest extends TestCase
{
    /**
     * @var int
     */
    private $max_age = 125;

    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->pool->registerType(User::class);
        $this->assertTrue($this->pool->isTypeRegistered(User::class));
    }

    public function testFieldNameIsRequired()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Value '' is not a valid field name");
        $validator = new Validator($this->connection, 'users', null, null, []);
        $validator->lowerThanOrEquals('', 123);
    }

    /**
     * Test if value that is lower than reference value passes validation.
     */
    public function testValueLowerThanPassesValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['age' => 83]);

        $is_valid_age = $validator->lowerThanOrEquals('age', $this->max_age);

        $this->assertTrue($is_valid_age);
        $this->assertFalse($validator->hasErrors());

        $age_errors = $validator->getFieldErrors('age');

        $this->assertIsArray($age_errors);
        $this->assertCount(0, $age_errors);
    }

    /**
     * Test if value that is lower than reference value passes validation.
     */
    public function testEqualValuePassesValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['age' => $this->max_age]);

        $is_valid_age = $validator->lowerThanOrEquals('age', $this->max_age);

        $this->assertTrue($is_valid_age);
        $this->assertFalse($validator->hasErrors());

        $age_errors = $validator->getFieldErrors('age');

        $this->assertIsArray($age_errors);
        $this->assertCount(0, $age_errors);
    }

    /**
     * Test if value that is not lower than the reference value does not pass validation.
     */
    public function testValueNotInArrayFailsValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['age' => 27356]);

        $is_valid_age = $validator->lowerThanOrEquals('age', $this->max_age);

        $this->assertFalse($is_valid_age);
        $this->assertTrue($validator->hasErrors());

        $age_errors = $validator->getFieldErrors('age');

        $this->assertIsArray($age_errors);
        $this->assertCount(1, $age_errors);
    }

    /**
     * Test if NULL value passes validation when it is allowed.
     */
    public function testNullPassesValidationWhenAllowed()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['age' => null]);

        $is_valid_age = $validator->lowerThanOrEquals('age', $this->max_age, true);

        $this->assertTrue($is_valid_age);
        $this->assertFalse($validator->hasErrors());

        $age_errors = $validator->getFieldErrors('age');

        $this->assertIsArray($age_errors);
        $this->assertCount(0, $age_errors);
    }

    /**
     * Test if NULL value fails validation when it is not allowed.
     */
    public function testNullFailsValidationWhenNotAllowed()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['age' => null]);

        $is_valid_age = $validator->lowerThanOrEquals('age', $this->max_age);

        $this->assertFalse($is_valid_age);
        $this->assertTrue($validator->hasErrors());

        $age_errors = $validator->getFieldErrors('age');

        $this->assertIsArray($age_errors);
        $this->assertCount(1, $age_errors);
    }
}
