<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Collection\Type;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
class Collection extends Type
{
    /**
     * Return type that this collection manages.
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
    protected function isReady(): bool
    {
        return $this->is_ready;
    }

    /**
     * Set collection as not ready.
     *
     * @return $this
     */
    public function &setAsNotReady()
    {
        $this->is_ready = false;

        return $this;
    }

    /**
     * @var string
     */
    private $additional_identifier = 'na';

    /**
     * @return string
     */
    protected function getAdditionalIdentifier()
    {
        return $this->additional_identifier;
    }

    /**
     * @param  string $value
     * @return $this
     */
    public function &setAdditionalIdenfitifier($value)
    {
        $this->additional_identifier = $value;

        return $this;
    }
}
