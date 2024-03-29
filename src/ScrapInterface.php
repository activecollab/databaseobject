<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;

interface ScrapInterface
{
    public function scrap(bool $bulk = false): EntityInterface;
}
