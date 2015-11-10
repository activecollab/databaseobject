<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Collection\Type;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
class Collection extends Type
{
    /**
     * Return type that this collection manages
     *
     * @return string
     */
    public function getType()
    {
        return Writer::class;
    }
}
