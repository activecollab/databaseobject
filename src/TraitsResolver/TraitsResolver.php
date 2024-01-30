<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\TraitsResolver;

use ReflectionClass;

class TraitsResolver implements TraitsResolverInterface
{
    private array $type_traits = [];

    public function getClassTraits(string $class_name): array
    {
        if (empty($this->type_traits[$class_name])) {
            $this->type_traits[$class_name] = [];

            $this->recursiveGetClassTraits(
                new ReflectionClass($class_name),
                $this->type_traits[$class_name],
            );
        }

        return $this->type_traits[$class_name];
    }

    /**
     * Recursively get trait names for the given class name.
     */
    private function recursiveGetClassTraits(ReflectionClass $class, array &$trait_names): void
    {
        $trait_names = array_merge($trait_names, $class->getTraitNames());

        if ($class->getParentClass()) {
            $this->recursiveGetClassTraits($class->getParentClass(), $trait_names);
        }
    }
}
