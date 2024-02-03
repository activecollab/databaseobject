<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;

interface ProducerInterface
{
    public function produce(
        string $type,
        array $attributes = null,
        bool $save = true,
    ): EntityInterface;

    public function modify(
        EntityInterface $instance,
        array $attributes = null,
        bool $save = true,
    ): EntityInterface;

    public function scrap(
        EntityInterface $instance,
        bool $force_delete = false,
    ): EntityInterface;
}
