<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits
 */
trait Russian
{
    /**
     * @var bool
     */
    public $is_russian = false;

    /**
     * Call when object is created.
     */
    public function ActiveCollabDatabaseObjectTestFixturesWritersTraitsRussian()
    {
        $this->is_russian = true;
    }
}
