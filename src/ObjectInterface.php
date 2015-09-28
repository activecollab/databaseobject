<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use ActiveCollab\DatabaseObject\Exception\ValidationException;
use JsonSerializable;
use InvalidArgumentException;

/**
 * @package ActiveCollab\DatabaseObject
 */
interface ObjectInterface extends LoadFromRow, JsonSerializable
{
    /**
     * Validate object properties before object is saved
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
     * Returns true if $var is the same object this object is
     *
     * Comparison is done on class - PK values for loaded objects, or as simple
     * object comparison in case objects are not saved and loaded
     *
     * @param  Object  $var
     * @return boolean
     */
    public function is(&$var);

    /**
     * Return object attributes
     *
     * This function will return array of attribute name -> attribute value pairs
     * for this specific project
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Return primary key columns
     *
     * @return array
     */
    public function getPrimaryKey();

    /**
     * Return value of table name
     *
     * @return string
     */
    public function getTableName();

    // ---------------------------------------------------
    //  CRUD methods
    // ---------------------------------------------------

    /**
     * Load data from database row
     *
     * If $cache_row is set to true row data will be added to cache
     *
     * @param  array                    $row
     * @throws InvalidArgumentException
     */
    public function loadFromRow(array $row);

    /**
     * Save object into database (insert or update)
     *
     * @return $this
     * @throws ValidationException
     */
    public function &save();

    /**
     * Delete specific object (and related objects if neccecery)
     *
     * @param  boolean $bulk
     * @return $this
     */
    public function &delete($bulk = false);

    /**
     * Create a copy of this object and optionally save it
     *
     * @param  boolean $save
     * @return Object
     */
    public function copy($save = false);

    // ---------------------------------------------------
    //  Flags
    // ---------------------------------------------------

    /**
     * Return value of $is_new variable
     *
     * @return boolean
     */
    public function isNew();

    /**
     * Returns true if this object have row in database
     *
     * @return boolean
     */
    public function isLoaded();

    // ---------------------------------------------------
    //  Fields
    // ---------------------------------------------------

    /**
     * Return object ID
     *
     * @return integer
     */
    public function getId();

    /**
     * Set value of id field
     *
     * @param  integer $value
     * @return $this
     */
    public function &setId($value);

    /**
     * Check if specific field is defined
     *
     * @param  string  $field Field name
     * @return boolean
     */
    public function fieldExists($field);

    /**
     * Return array of modified fields
     *
     * @return array
     */
    public function getModifiedFields();

    /**
     * Check if this object has modified columns
     *
     * @return boolean
     */
    public function isModified();

    /**
     * Returns true if specific field is modified
     *
     * @param  string  $field
     * @return boolean
     */
    public function isModifiedField($field);

    /**
     * Return true if primary key is modified
     *
     * @return bool
     */
    public function isPrimaryKeyModified();

    /**
     * Revert field to old value
     *
     * @param $field
     */
    public function revertField($field);

    /**
     * Check if selected field is primary key
     *
     * @param  string  $field Field that need to be checked
     * @return boolean
     */
    public function isPrimaryKey($field);

    /**
     * Return list of fields
     */
    public function getFields();

    /**
     * Return value of specific field and typecast it...
     *
     * @param  string $field   Field value
     * @param  mixed  $default Default value that is returned in case of any error
     * @return mixed
     */
    public function getFieldValue($field, $default = null);

    /**
     * Return old field values, before fields were updated
     *
     * @return array
     */
    public function getOldValues();

    /**
     * Return all field value
     *
     * @param string $field
     * @return mixed
     */
    public function getOldFieldValue($field);

    /**
     * Set specific field value
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
     * Set non-field value during DataManager::create() and DataManager::update() calls
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @return $this
     */
    public function &setAttribute($attribute, $value);
}