<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Exception;

use Exception;
use RuntimeException;

/**
 * @package ActiveCollab\DatabaseObject\Exception
 */
class ObjectNotFoundException extends RuntimeException
{
    /**
     * @param string         $type
     * @param int            $id
     * @param Exception|null $previous
     */
    public function __construct($type, $id, Exception $previous = null)
    {
        parent::__construct("{$type} #{$id} not found", 0, $previous);
    }
}
