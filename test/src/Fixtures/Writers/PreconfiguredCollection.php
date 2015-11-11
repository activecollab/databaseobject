<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Collection\Type;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
class PreconfiguredCollection extends Type
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

    /**
     * Configure the collection when it is created
     */
    protected function configure()
    {
        $this->where('`name` LIKE ?', 'A%')->orderBy('`name`');
    }
}
