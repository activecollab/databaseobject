<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Exception;

use Exception;
use RuntimeException;

class ObjectNotFoundException extends RuntimeException
{
    public function __construct(
        string $type,
        int $id,
        Exception $previous = null,
    )
    {
        parent::__construct(
            sprintf("%s #%d not found", $type, $id),
            0,
            $previous,
        );
    }
}
