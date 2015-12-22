<?php

namespace ActiveCollab\DatabaseObject\ContainerAccessInterface;

use ActiveCollab\DatabaseObject\ContainerAccessInterface;
use Interop\Container\ContainerInterface;
use LogicException;

/**
 * @package ActiveCollab\DatabaseObjectContainerAccessInterface
 */
trait Implementation
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function hasContainer()
    {
        return $this->container instanceof ContainerInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function &getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function &setContainer(ContainerInterface &$container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Bridge container get
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->container) {
            return $this->container->get($name);
        } else {
            throw new LogicException('Container is not set');
        }
    }

    /**
     * Bridge container has
     *
     * @param  string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->container ? $this->container->has($name) : false;
    }
}
