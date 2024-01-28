<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Entity;

use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use ActiveCollab\DatabaseObject\ValidatorInterface;
use ActiveCollab\Object\ObjectInterface;
use JsonSerializable;

interface EntityInterface extends ObjectInterface, LoadFromRow, JsonSerializable
{
    /**
     * Load data from database row.
     *
     * If $cache_row is set to true row data will be added to cache.
     */
    public function loadFromRow(array $row): static;

    /**
     * Validate object properties before object is saved.
     *
     * This method is called before the item is saved and can be used to fetch
     * errors in data before we really save it to the database. $errors is instance
     * of ValidationErrors class that is used for error collection. If collection
     * is empty object is considered valid and save process will continue
     */
    public function validate(ValidatorInterface $validator): ValidatorInterface;

    /**
     * Return true if $object is the same as this object (of same type, and
     * with the same ID).
     */
    public function is(mixed $object): bool;

    /**
     * Save object into database (insert or update).
     */
    public function save(): static;

    /**
     * Create a copy of this object and optionally save it.
     */
    public function copy(bool $save = false): static;

    /**
     * Delete specific object (and related objects if necessary).
     */
    public function delete(bool $bulk = false): static;

    /**
     * Return primary key columns.
     */
    public function getPrimaryKey(): string;

    /**
     * Return value of table name.
     */
    public function getTableName(): string;

    // ---------------------------------------------------
    //  Flags
    // ---------------------------------------------------

    /**
     * Return value of $is_new variable.
     */
    public function isNew(): bool;

    /**
     * Returns true if this object have row in database.
     */
    public function isLoaded(): bool;

    // ---------------------------------------------------
    //  Fields
    // ---------------------------------------------------

    public function getId(): ?int;

    /**
     * Set value of id field.
     */
    public function setId(?int $value): static;

    /**
     * Check if this object has modified columns.
     */
    public function isModified(): bool;

    /**
     * Return modifications indexed by field name, with value composed of an old and new value.
     */
    public function getModifications(): array;

    /**
     * Return array of modified fields.
     */
    public function getModifiedFields(): array;

    /**
     * Returns true if specific field is modified.
     */
    public function isModifiedField(string $field): bool;

    /**
     * Return a list of modified attributes.
     */
    public function getModifiedAttributes(): array;

    /**
     * Return true if $attribute is modified.
     */
    public function isModifiedAttribute(string $attribute): bool;

    /**
     * Return a list of fields that are managed by this entity.
     */
    public function getEntityFields(): array;

    /**
     * @deprecated Use entityFieldExists() instead.
     */
    public function fieldExists($field);

    /**
     * Return true if $field exists (both generated and non-generated fields are checked).
     */
    public function entityFieldExists(string $entity_field): bool;

    /**
     * Return a list of fields that this entity is aware of, but does not manage.
     *
     * @return array
     */
    public function getGeneratedFields();

    /**
     * Check if generated field exists.
     *
     * @param  string $field Field name
     * @return bool
     */
    public function generatedFieldExists($field);

    /**
     * Return true if $field is generated field.
     *
     * @param  string $field
     * @return bool
     */
    public function isGeneratedField($field);

    /**
     * Check if selected field is primary key.
     */
    public function isPrimaryKey(string $field): bool;

    /**
     * Return true if primary key is modified.
     */
    public function isPrimaryKeyModified(): bool;

    /**
     * Return value of specific field and typecast it.
     */
    public function getFieldValue(string $field, mixed $default = null): mixed;

    /**
     * Return old field values, before fields were updated.
     *
     * @return array
     */
    public function getOldValues();

    /**
     * Return all field value.
     *
     * @param  string $field
     * @return mixed
     */
    public function getOldFieldValue($field);

    /**
     * Revert field to old value.
     */
    public function revertField(string $field): void;

    /**
     * Set specific field value.
     *
     * Set value of the $field. This function will make sure that everything
     * runs fine - modifications are saved, in case of primary key old value
     * will be remembered in case we need to update the row and so on
     */
    public function setFieldValue(string $field, mixed $value): static;

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls.
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @return $this
     */
    public function &setAttribute($attribute, $value);

    /**
     * Return an array of object properties that are needed to fully display this object on a page.
     *
     * @return array
     */
    public function jsonSerializeDetails();
}
