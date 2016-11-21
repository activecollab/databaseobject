<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Fixtures;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\DatabaseObject\Producer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Users
 */
class CustomProducer extends Producer
{
    /**
     * Produce new instance of $type.
     *
     * @param  string          $type
     * @param  array|null      $attributes
     * @param  bool            $save
     * @return EntityInterface
     */
    public function &produce($type, array $attributes = null, $save = true)
    {
        $object = parent::produce($type, $attributes, $save);

        if ($object instanceof Writer && array_key_exists('custom_producer_set_custom_attribute_to', $attributes)) {
            $object->custom_attribute_value = $attributes['custom_producer_set_custom_attribute_to'];
        }

        return $object;
    }

    /**
     * Update an instance.
     *
     * @param  EntityInterface $instance
     * @param  array|null      $attributes
     * @param  bool            $save
     * @return EntityInterface
     */
    public function &modify(EntityInterface &$instance, array $attributes = null, $save = true)
    {
        $instance = parent::modify($instance, $attributes, $save);

        if ($instance instanceof Writer) {
            $instance->modified_using_custom_producer = true;
        }

        return $instance;
    }

    /**
     * Scrap an instance (move it to trash, if object can be trashed, or delete it).
     *
     * @param  EntityInterface $instance
     * @param  bool            $force_delete
     * @return EntityInterface
     */
    public function &scrap(EntityInterface &$instance, $force_delete = false)
    {
        $instance = parent::scrap($instance, $force_delete);

        if ($instance instanceof Writer) {
            $instance->scrapped_using_custom_producer = true;
        }

        return $instance;
    }
}
