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
trait ClassicWriter
{
    /**
     * @var bool
     */
    public $is_classic_writer = false;

    /**
     * Call when object is created.
     */
    public function ActiveCollabDatabaseObjectTestFixturesWritersTraitsClassicWriter()
    {
        $this->is_classic_writer = true;
    }
}
