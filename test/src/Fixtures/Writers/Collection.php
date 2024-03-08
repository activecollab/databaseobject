<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Collection\Type;

class Collection extends Type
{
    public function getType(): string
    {
        return Writer::class;
    }

    private bool $is_ready = true;

    protected function isReady(): bool
    {
        return $this->is_ready;
    }

    /**
     * Set collection as not ready.
     */
    public function setAsNotReady(): static
    {
        $this->is_ready = false;

        return $this;
    }

    private string $additional_identifier = 'na';

    protected function getAdditionalIdentifier(): string
    {
        return $this->additional_identifier;
    }

    public function setAdditionalIdentifier(string $value): static
    {
        $this->additional_identifier = $value;

        return $this;
    }
}
