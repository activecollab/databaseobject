<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\FinderFactory;

use ActiveCollab\DatabaseObject\FinderInterface;

interface FinderFactoryInterface
{
    public function produceFinder(
        string $type,
        string $where_pattern = null,
        mixed ...$where_arguments,
    ): FinderInterface;
}
