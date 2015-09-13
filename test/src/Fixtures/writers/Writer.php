<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Validator;

class Writer extends BaseWriter
{
    /**
     * @param Validator $validator
     */
    public function validate(Validator &$validator)
    {
        $validator->notEmpty('name');
        $validator->notEmpty('birthday');

        parent::validate($validator);
    }
}