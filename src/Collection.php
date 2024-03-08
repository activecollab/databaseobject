<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\Etag\EtagInterface\Implementation as EtagInterfaceImplementation;
use LogicException;
use Psr\Log\LoggerInterface;

abstract class Collection implements CollectionInterface
{
    use EtagInterfaceImplementation;

    public function __construct(
        protected ConnectionInterface $connection,
        protected PoolInterface $pool,
        protected LoggerInterface $logger,
    )
    {
        $this->configure();
    }

    protected function configure(): void
    {
    }

    /**
     * Return true if ready.
     *
     * If collection declares that it is not ready, but execute methods get called, we should throw an exception
     */
    protected function isReady(): bool
    {
        return true;
    }

    private string $application_identifier = 'APPv1.0';

    public function getApplicationIdentifier(): string
    {
        return $this->application_identifier;
    }

    public function setApplicationIdentifier(string $value): static
    {
        $this->application_identifier = $value;

        return $this;
    }

    /**
     * Prepare collection tag from bits of information.
     */
    protected function prepareTagFromBits(
        string $additional_identifier,
        string $visitor_identifier,
        string $hash
    ): string
    {
        return implode(
            ',',
            [
                $this->getApplicationIdentifier(),
                'collection',
                get_class($this),
                $additional_identifier,
                $visitor_identifier,
                $hash,
            ]
        );
    }

    public function canBeTagged(): bool
    {
        return true;
    }

    public function pagination(
        int $current_page = 1,
        int $items_per_page = 100,
    ): static
    {
        $this->is_paginated = true;

        $this->currentPage($current_page);

        $this->items_per_page = $items_per_page;

        if ($this->items_per_page < 1) {
            $this->items_per_page = 100;
        }

        return $this;
    }

    private bool $is_paginated = false;

    public function isPaginated(): bool
    {
        return $this->is_paginated;
    }

    private ?int $current_page = null;
    private ?int $items_per_page = null;

    public function currentPage(?int $value): static
    {
        if (!$this->is_paginated) {
            throw new LogicException('Page can be set only for paginated collections');
        }

        $this->current_page = (int) $value;

        if ($this->current_page < 1) {
            $this->current_page = 1;
        }

        return $this;
    }

    public function getCurrentPage(): ?int
    {
        return $this->current_page;
    }

    public function getItemsPerPage(): ?int
    {
        return $this->items_per_page;
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): mixed
    {
        $result = $this->execute();

        if ($result instanceof ResultInterface) {
            return $result->jsonSerialize();
        }

        if ($result === null) {
            return [];
        }

        return $result;
    }
}
