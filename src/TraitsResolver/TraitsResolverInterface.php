<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\TraitsResolver;

interface TraitsResolverInterface
{
    /**
     * Return trait names for the given class.
     *
     * @param  string $class_name
     * @return array
     */
    public function getClassTraits($class_name);
}
