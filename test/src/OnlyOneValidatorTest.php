<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Validator;

class OnlyOneValidatorTest extends WritersTypeTestCase
{
    public function testRecordWithDifferentFieldValueIsValidated(): void
    {
        $validator = new Validator(
            $this->connection,
            'writers',
            null,
            null,
            [
                'name' => 'Alexander Pushkin',
            ]
        );

        $is_only_one = $validator->onlyOne('name', 'Not Alexander Pushkin');

        $this->assertTrue($is_only_one);
        $this->assertEmpty($validator->getFieldErrors('name'));
    }

    public function testWillValidateMatchingValueThatDoesNotExist(): void
    {
        $validator = new Validator(
            $this->connection,
            'writers',
            null,
            null,
            [
                'name' => 'Anton Chekhov',
            ]
        );

        $is_only_one = $validator->onlyOne('name', 'Alexander Pushkin');

        $this->assertTrue($is_only_one);
        $this->assertEmpty($validator->getFieldErrors('name'));
    }

    public function testWillInvalidateMatchingValueThatAlreadyExists(): void
    {
        $validator = new Validator(
            $this->connection,
            'writers',
            null,
            null,
            [
                'name' => 'Alexander Pushkin',
            ]
        );

        $is_only_one = $validator->onlyOne('name', 'Alexander Pushkin');

        $this->assertFalse($is_only_one);

        $name_errors = $validator->getFieldErrors('name');
        $this->assertNotEmpty($name_errors);
        $this->assertSame(
            "Only one record with field 'name' set to 'Alexander Pushkin' is allowed.",
            $name_errors[0]
        );
    }

    public function testWillValidateMatchingValueWhenContextIsNotTheSame(): void
    {
        $validator = new Validator(
            $this->connection,
            'writers',
            null,
            null,
            [
                'name' => 'Alexander Pushkin',

                // Same writer, different birthday (Pushkin's birthday is '1799-06-06').
                'birthday' => '1821-11-11',
            ]
        );

        $is_only_one = $validator->onlyOne('name', 'Alexander Pushkin', 'birthday');

        $this->assertTrue($is_only_one);
        $this->assertEmpty($validator->getFieldErrors('name'));
    }

    public function testWillInvalidateMatchingValueThatAlreadyExistsInGivenContext(): void
    {
        $validator = new Validator(
            $this->connection,
            'writers',
            null,
            null,
            [
                'name' => 'Alexander Pushkin',
                'birthday' => '1799-06-06',
            ]
        );

        $is_only_one = $validator->onlyOne('name', 'Alexander Pushkin', 'birthday');

        $this->assertFalse($is_only_one);

        $name_errors = $validator->getFieldErrors('name');
        $this->assertNotEmpty($name_errors);
        $this->assertSame(
            "Only one record with field 'name' set to 'Alexander Pushkin' is allowed in context of 'birthday'.",
            $name_errors[0]
        );
    }
}
