<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits;

trait ClassicWriter
{
    public bool $is_classic_writer = false;

    public function ActiveCollabDatabaseObjectTestFixturesWritersTraitsClassicWriter()
    {
        $this->is_classic_writer = true;
    }
}
