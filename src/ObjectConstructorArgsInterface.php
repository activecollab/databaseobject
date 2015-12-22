<?php

namespace ActiveCollab\DatabaseObject;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ObjectConstructorArgsInterface
{
    /**
     * @return array
     */
    public function getObjectConstructorArgs();

    /**
     * @param  array $args
     * @return $this
     */
    public function &setObjectConstructorArgs(array $args);
}
