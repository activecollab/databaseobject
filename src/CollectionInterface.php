<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use ActiveCollab\Etag\EtagInterface;
use JsonSerializable;

interface CollectionInterface extends EtagInterface, JsonSerializable
{
    public function getApplicationIdentifier(): string;
    public function setApplicationIdentifier(string $value): static;
    public function canBeTagged(): bool;

    /**
     * Run the query and return DB result.
     *
     * @return ResultInterface|EntityInterface[]
     */
    public function execute(): ?iterable;

    /**
     * Return ID-s of matching records.
     */
    public function executeIds(): array;

    /**
     * Return number of records that match conditions set by the collection.
     */
    public function count(): int;

    /**
     * Set pagination configuration.
     */
    public function pagination(
        int $current_page = 1,
        int $items_per_page = 100,
    ): static;

    /**
     * Return true if collection is paginated.
     */
    public function isPaginated(): bool;

    /**
     * Set current page.
     */
    public function currentPage(?int $value): static;

    /**
     * Return current page #.
     */
    public function getCurrentPage(): ?int;

    /**
     * Return number of items that are displayed per page.
     */
    public function getItemsPerPage(): ?int;
}
