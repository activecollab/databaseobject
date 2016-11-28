<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ProducerInterface
{
    /**
     * Produce new instance of $type.
     *
     * @param  string          $type
     * @param  array|null      $attributes
     * @param  bool            $save
     * @return EntityInterface
     */
    public function &produce($type, array $attributes = null, $save = true);

    /**
     * Update an instance.
     *
     * @param  EntityInterface $instance
     * @param  array|null      $attributes
     * @param  bool            $save
     * @return EntityInterface
     */
    public function &modify(EntityInterface &$instance, array $attributes = null, $save = true);

    /**
     * Scrap an instance (move it to trash, if object supports, or delete it).
     *
     * @param  EntityInterface $instance
     * @param  bool            $force_delete
     * @return EntityInterface
     */
    public function &scrap(EntityInterface &$instance, $force_delete = false);
}
