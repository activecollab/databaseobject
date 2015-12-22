<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures
 */
class Container extends \Pimple\Container implements ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws InvalidArgumentException No entry was found for this identifier.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }
        return $this->offsetGet($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }
}
