<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test\Fixtures\StatSnapshots;

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
