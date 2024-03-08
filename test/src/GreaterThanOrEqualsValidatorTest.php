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
use InvalidArgumentException;

class GreaterThanOrEqualsValidatorTest extends TestCase
{
    /**
     * @var int
     */
    private $min_age = 18;

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
        $this->expectExceptionMessage("Value '' is not a valid field name");
        $this->expectException(InvalidArgumentException::class);
        $validator = new Validator($this->connection, 'users', null, null, []);
        $validator->greaterThanOrEquals('', 123);
    }

    /**
     * Test if value that is greater than reference value passes validation.
     */
    public function testValueGreaterThanPassesValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['age' => 45]);

        $is_valid_age = $validator->greaterThanOrEquals('age', $this->min_age);

        $this->assertTrue($is_valid_age);
        $this->assertFalse($validator->hasErrors());

        $age_errors = $validator->getFieldErrors('age');

        $this->assertIsArray($age_errors);
        $this->assertCount(0, $age_errors);
    }

    /**
     * Test if value that is equal to the reference value passes validation.
     */
    public function testEqualValueThanPassesValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['age' => 18]);

        $is_valid_age = $validator->greaterThanOrEquals('age', $this->min_age);

        $this->assertTrue($is_valid_age);
        $this->assertFalse($validator->hasErrors());

        $age_errors = $validator->getFieldErrors('age');

        $this->assertIsArray($age_errors);
        $this->assertCount(0, $age_errors);
    }

    /**
     * Test if value that is not greater than the reference value does not pass validation.
     */
    public function testValueNotInArrayFailsValidation()
    {
        $validator = new Validator($this->connection, 'users', null, null, ['age' => 7]);

        $is_valid_age = $validator->greaterThanOrEquals('age', $this->min_age);

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

        $is_valid_age = $validator->greaterThanOrEquals('age', $this->min_age, true);

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

        $is_valid_age = $validator->greaterThanOrEquals('age', $this->min_age);

        $this->assertFalse($is_valid_age);
        $this->assertTrue($validator->hasErrors());

        $age_errors = $validator->getFieldErrors('age');

        $this->assertIsArray($age_errors);
        $this->assertCount(1, $age_errors);
    }
}
