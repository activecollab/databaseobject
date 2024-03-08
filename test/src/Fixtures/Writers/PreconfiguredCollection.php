<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Collection\Type;

class PreconfiguredCollection extends Type
{
    public function getType(): string
    {
        return Writer::class;
    }

    protected function configure(): void
    {
        $this->where('`name` LIKE ?', 'A%')->orderBy('`name`');
    }
}
