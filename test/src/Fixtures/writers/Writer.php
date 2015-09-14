<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Validator;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\Russian;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\ClassicWriter;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
class Writer extends BaseWriter
{
    use Russian, ClassicWriter;

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