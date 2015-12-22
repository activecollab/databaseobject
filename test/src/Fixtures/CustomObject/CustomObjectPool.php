<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\CustomObject;

use ActiveCollab\DatabaseObject\Pool;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\CustomFinder
 */
class CustomObjectPool extends Pool
{
    /**
     * @param  string $registered_type
     * @return array
     */
    public function getObjectConstructorArgs($registered_type)
    {
        return array_merge(parent::getObjectConstructorArgs($registered_type), [true]);
    }
}
