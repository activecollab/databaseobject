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
    public function testNewRecordDoesNotReportAnErrorWhenTheresNoDuplicateValue()
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

        $is_only_one = $validator->onlyOneWhere('name', 'Anton Chekhov', '');

        $this->assertTrue($is_only_one);
        $this->assertFalse($validator->hasErrors());

        $name_errors = $validator->getFieldErrors('name');

        $this->assertIsArray($name_errors);
        $this->assertCount(0, $name_errors);
    }
}
