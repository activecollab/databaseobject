<?php

namespace ActiveCollab\DatabaseObject;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ProducerInterface extends ObjectConstructorArgsInterface
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

    /**
     * Update an instance
     *
     * @param  ObjectInterface $instance
     * @param  array|null      $attributes
     * @param  boolean         $save
     * @return ObjectInterface
     */
    public function &modify(ObjectInterface &$instance, array $attributes = null, $save = true);

    /**
     * Scrap an instance (move it to trash, if object supports, or delete it)
     *
     * @param  ObjectInterface $instance
     * @param  boolean         $force_delete
     * @return ObjectInterface
     */
    public function &scrap(ObjectInterface &$instance, $force_delete = false);
}
