<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures;

use ActiveCollab\DatabaseObject\ObjectInterface;
use ActiveCollab\DatabaseObject\Producer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Users
 */
class CustomProducer extends Producer
{
    /**
     * Produce new instance of $type
     *
     * @param  string          $type
     * @param  array|null      $attributes
     * @param  boolean         $save
     * @return ObjectInterface
     */
    public function &produce($type, array $attributes = null, $save = true)
    {
        $object = parent::produce($type, $attributes, $save);

        if ($object instanceof Writer && array_key_exists('custom_producer_set_custom_attribute_to', $attributes)) {
            $object->custom_attribute_value = $attributes['custom_producer_set_custom_attribute_to'];
        }

        return $object;
    }
}