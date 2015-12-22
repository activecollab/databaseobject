<?php

namespace ActiveCollab\DatabaseObject;

use Interop\Container\ContainerInterface;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ContainerAccessInterface
{
    /**
     * @return boolean
     */
    public function hasContainer();

    /**
     * Return container instance
     *
     * @return ContainerInterface
     */
    public function getContainer();

    /**
     * @param  ContainerInterface $container
     * @return $this
     */
    public function &setContainer(ContainerInterface &$container);
}
