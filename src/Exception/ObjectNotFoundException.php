<?php

namespace ActiveCollab\DatabaseObject\Exception;

use RuntimeException;
use Exception;

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
