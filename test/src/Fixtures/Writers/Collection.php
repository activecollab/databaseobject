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

    private $is_ready = true;

    /**
     * {@inheritdoc}
     */
    protected function isReady()
    {
        return $this->is_ready;
    }

    /**
     * Set collection as not ready
     *
     * @return $this
     */
    public function &setAsNotReady()
    {
        $this->is_ready = false;

        return $this;
    }
}
