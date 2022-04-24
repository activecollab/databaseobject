<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\TestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\SpatialEntity\Base\SpatialEntity;

class EscapedTypeFieldsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->pool->registerType(SpatialEntity::class);
    }

    public function testWillEscapeFieldNames(): void
    {
        $this->assertSame(
            '`spatial_entities`.`id`,`spatial_entities`.`name`,`spatial_entities`.`polygon`',
            $this->pool->getEscapedTypeFields(SpatialEntity::class)
        );
    }

    public function testWillPrepareTypeSqlReadStatement(): void
    {
        $this->assertSame(
            "`spatial_entities`.`id`,`spatial_entities`.`name`,ST_GEOMFROMTEXT(`spatial_entities`.`polygon`) AS 'polygon'",
            $this->pool->getTypeFieldsReadStatement(SpatialEntity::class)
        );
    }
}
