<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Entity;

/**
 * @package ActiveCollab\DatabaseObject\Entity
 */
interface ManagerInterface
{
    /**
     * Return type that this manager works with.
     *
     * @return string
     */
    public function getType();
}
