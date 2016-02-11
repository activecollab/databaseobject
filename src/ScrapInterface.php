<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ScrapInterface
{
    /**
     * Scrap the object, instead of permanently deleting it.
     *
     * @param  bool|false $bulk
     * @return $this
     */
    public function &scrap($bulk = false);
}
