<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Entity;

use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use ActiveCollab\DatabaseObject\Exception\ValidationException;
use ActiveCollab\DatabaseObject\ValidatorInterface;
use ActiveCollab\Object\ObjectInterface;
use InvalidArgumentException;
use JsonSerializable;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface EntityInterface extends ObjectInterface, LoadFromRow, JsonSerializable
{
    /**
     * Load data from database row.
     *
     * If $cache_row is set to true row data will be added to cache
     *
     * @param  array                    $row
     * @throws InvalidArgumentException
     */
    public function loadFromRow(array $row);

    /**
     * Validate object properties before object is saved.
     *
     * This method is called before the item is saved and can be used to fetch
     * errors in data before we really save it database. $errors is instance of
     * ValidationErrors class that is used for error collection. If collection
     * is empty object is considered valid and save process will continue
     *
     * @param ValidatorInterface $validator
     */
    public function validate(ValidatorInterface &$validator);

    /**
     * Save object into database (insert or update).
     *
     * @return $this
     * @throws ValidationException
     */
    public function &save();

    /**
     * Create a copy of this object and optionally save it.
     *
     * @param  bool            $save
     * @return EntityInterface
     */
    public function copy($save = false);

    /**
     * Delete specific object (and related objects if neccecery).
     *
     * @param  bool  $bulk
     * @return $this
     */
    public function &delete($bulk = false);

    /**
     * Return primary key columns.
     *
     * @return string
     */
    public function getPrimaryKey();

    /**
     * Return value of table name.
     *
     * @return string
     */
    public function getTableName();

    // ---------------------------------------------------
    //  Flags
    // ---------------------------------------------------

    /**
     * Return value of $is_new variable.
     *
     * @return bool
     */
    public function isNew();

    /**
     * Returns true if this object have row in database.
     *
     * @return bool
     */
    public function isLoaded();

    // ---------------------------------------------------
    //  Fields
    // ---------------------------------------------------

    /**
     * Set value of id field.
     *
     * @param  int   $value
     * @return $this
     */
    public function &setId($value);

    /**
     * Check if this object has modified columns.
     *
     * @return bool
     */
    public function isModified();

    /**
     * Return modificications indexed by field name, with value composed of an old and new value.
     *
     * @return array
     */
    public function getModifications();

    /**
     * Return array of modified fields.
     *
     * @return array
     */
    public function getModifiedFields();

    /**
     * Returns true if specific field is modified.
     *
     * @param  string $field
     * @return bool
     */
    public function isModifiedField($field);

    /**
     * Return a list of modified attributes.
     *
     * @return array
     */
    public function getModifiedAttributes();

    /**
     * Return true if $attribute is modified.
     *
     * @param  string $attribute
     * @return bool
     */
    public function isModifiedAttribute($attribute);

    /**
     * Return a list of fields that are managed by this entity.
     *
     * @return array
     */
    public function getFields();

    /**
     * Return true if $field exists (both generated and non-generated fields are checked).
     *
     * @param  string $field Field name
     * @return bool
     */
    public function fieldExists($field);

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
     *
     * @param  string $field Field that need to be checked
     * @return bool
     */
    public function isPrimaryKey($field);

    /**
     * Return true if primary key is modified.
     *
     * @return bool
     */
    public function isPrimaryKeyModified();

    /**
     * Return value of specific field and typecast it...
     *
     * @param  string $field   Field value
     * @param  mixed  $default Default value that is returned in case of any error
     * @return mixed
     */
    public function getFieldValue($field, $default = null);

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
     *
     * @param string $field
     */
    public function revertField($field);

    /**
     * Set specific field value.
     *
     * Set value of the $field. This function will make sure that everything
     * runs fine - modifications are saved, in case of primary key old value
     * will be remembered in case we need to update the row and so on
     *
     * @param  string                   $field
     * @param  mixed                    $value
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function &setFieldValue($field, $value);

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
