<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Validator;

/**
 * @package ActiveCollab\DatabaseObject\Test
 */
class PresenceValidatorTest extends WritersTypeTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value '' is not a valid field name
     */
    public function testFieldNameIsRequired()
    {
        $validator = new Validator($this->connection, 'writers', null, null, []);
        $validator->present('');
    }

    /**
     * Test validation pass.
     */
    public function testPass()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => 'Is present!']);

        $is_present = $validator->present('name');

        $this->assertTrue($is_present);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(0, $name_errors);
    }

    /**
     * Test validation failure.
     */
    public function testFail()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => '']);

        $is_present = $validator->present('name');

        $this->assertFalse($is_present);
        $this->assertTrue($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(1, $name_errors);
    }

    /**
     * @dataProvider provideBooleanValues
     * @param bool $value_to_check
     */
    public function testBooleanPresencePass(bool $value_to_check)
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['is_alive' => $value_to_check]);

        $this->assertTrue($validator->present('is_alive'));
        $this->assertFalse($validator->hasErrors());
    }

    public function provideBooleanValues()
    {
        return [
            [true],
            [false],
        ];
    }

    public function testBooleanPresenceFail()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['is_alive' => null]);

        $this->assertFalse($validator->present('is_alive'));
        $this->assertTrue($validator->hasErrors());

        $is_alive_errors = $validator->getFieldErrors('is_alive');

        $this->assertInternalType('array', $is_alive_errors);
        $this->assertCount(1, $is_alive_errors);
    }

    /**
     * Test fail because there is no value.
     */
    public function testFailBecauseTheresNoValue()
    {
        $validator = new Validator($this->connection, 'writers', null, null, []);

        $is_present = $validator->present('name');

        $this->assertFalse($is_present);
        $this->assertTrue($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(1, $name_errors);
    }

    /**
     * Test type check.
     */
    public function testTypeCheck()
    {
        $validator = new Validator($this->connection, 'writers', null, null, ['name' => '0']);

        $is_present = $validator->present('name');

        $this->assertTrue($is_present);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertInternalType('array', $name_errors);
        $this->assertCount(0, $name_errors);
    }
}
