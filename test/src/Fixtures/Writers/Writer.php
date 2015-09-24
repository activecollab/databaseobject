<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\Russian;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits\ClassicWriter;
use ActiveCollab\DatabaseObject\ValidatorInterface;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
class Writer extends BaseWriter
{
    use Russian, ClassicWriter;

    /**
     * @param ValidatorInterface $validator
     */
    public function validate(ValidatorInterface &$validator)
    {
        $validator->notEmpty('name');
        $validator->notEmpty('birthday');

        parent::validate($validator);
    }
}