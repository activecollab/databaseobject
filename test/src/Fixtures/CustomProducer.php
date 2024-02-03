<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test\Fixtures;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\DatabaseObject\Producer;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Writer;

class CustomProducer extends Producer
{
    public function produce(
        string $type,
        array $attributes = null,
        bool $save = true,
    ): EntityInterface
    {
        $object = parent::produce($type, $attributes, $save);

        if ($object instanceof Writer && array_key_exists('custom_producer_set_custom_attribute_to', $attributes)) {
            $object->custom_attribute_value = $attributes['custom_producer_set_custom_attribute_to'];
        }

        return $object;
    }

    public function modify(
        EntityInterface $instance,
        array $attributes = null,
        bool $save = true,
    ): EntityInterface
    {
        $instance = parent::modify($instance, $attributes, $save);

        if ($instance instanceof Writer) {
            $instance->modified_using_custom_producer = true;
        }

        return $instance;
    }

    public function scrap(
        EntityInterface $instance,
        bool $force_delete = false,
    ): EntityInterface
    {
        $instance = parent::scrap($instance, $force_delete);

        if ($instance instanceof Writer) {
            $instance->scrapped_using_custom_producer = true;
        }

        return $instance;
    }
}
