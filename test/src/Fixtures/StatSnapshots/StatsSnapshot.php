<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Test\Fixtures\StatSnapshots;

/**
 * @package ActiveCollab\Shepherd\Model
 */
class StatsSnapshot extends Base\StatsSnapshot
{
    /**
     * {@inheritdoc}
     */
    public function getStat($name)
    {
        $stats = $this->getStats();

        if (empty($stats[$name]) && !array_key_exists($name, $stats)) {
            return null;
        }

        return $stats[$name];
    }
}
