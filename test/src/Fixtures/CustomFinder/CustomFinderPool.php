<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\CustomFinder;

use ActiveCollab\DatabaseObject\Pool;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\CustomFinder
 */
class CustomFinderPool extends Pool
{
    /**
     * @return string
     */
    public function getDefaultFinderClass()
    {
        return CustomFinderFinder::class;
    }

    /**
     * @param  string $registered_type
     * @return array
     */
    public function getFinderConstructorArgs($registered_type)
    {
        return array_merge(parent::getFinderConstructorArgs($registered_type), ['it works!']);
    }
}
