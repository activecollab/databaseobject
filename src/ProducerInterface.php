<?php

namespace ActiveCollab\DatabaseObject;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ProducerInterface
{
    /**
     * Produce new instance of $type
     *
     * @param  string          $type
     * @param  array|null      $attributes
     * @param  boolean         $save
     * @return ObjectInterface
     */
    public function &produce($type, array $attributes = null, $save = true);
}
