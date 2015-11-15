<?php

namespace ActiveCollab\DatabaseObject;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ScrapInterface
{
    /**
     * Scrap the object, instead of permanently deleting it
     *
     * @param  bool|false $bulk
     * @return $this
     */
    public function &scrap($bulk = false);
}
