<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\TraitsResolver;

interface TraitsResolverInterface
{
    /**
     * Return trait names for the given class.
     */
    public function getClassTraits(string $class_name): array;
}
