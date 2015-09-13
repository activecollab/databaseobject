<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseObject\Exception\ValidationException;
use InvalidArgumentException;

abstract class Object
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Name of the table
     *
     * @var string
     */
    protected $table_name;

    /**
     * Array of field names
     *
     * @var array
     */
    protected $fields;

    /**
     * List of default field values
     *
     * @var array
     */
    protected $default_field_values = [];

    /**
     * Array of PK fields
     *
     * @var array
     */
    protected $primary_key = [];

    /**
     * Name of autoincrement field (if exists)
     *
     * @var string
     */
    protected $auto_increment = null;

    /**
     * List of protected fields (can't be set using setAttributes() method)
     *
     * @var array
     */
    protected $protect = null;

    /**
     * List of accepted fields
     *
     * @var array
     */
    protected $accept = null;

//    /**
//     * Construct data object and if $id is present load
//     *
//     * @param mixed $id
//     */
//    public function __construct($id = null)
//    {
//        if ($id !== null) {
//            $this->load($id);
//        }
//    }

    /**
     * @param Pool       $pool
     * @param Connection $connection
     */
    public function __construct(Pool $pool, Connection $connection)
    {
        $this->pool = $pool;
        $this->connection = $connection;
    }

    /**
     * Return name of this model
     *
     * @param  boolean $underscore
     * @param  boolean $singular
     * @return string
     */
    abstract public function getModelName($underscore = false, $singular = false);

    // ---------------------------------------------------
    //  Internals, not overridable
    // ---------------------------------------------------

    /**
     * Indicates if this is new object (not saved)
     *
     * @var boolean
     */
    private $is_new = true;

    /**
     * This flag is set to true when data from row are inserted into fields
     *
     * @var boolean
     */
    private $is_loading = false;

    /**
     * Field values
     *
     * @var array
     */
    private $values = [];

    /**
     * Array of modified field values
     *
     * Elements of this array are populated on setter call. Real name is
     * resolved, old value is saved here (if exists) and new one is set. Keys
     * used in this array are real field names only!
     *
     * @var array
     */
    private $old_values = [];

    /**
     * Array of modified fiels
     *
     * @var array
     */
    private $modified_fields = [];

    /**
     * Primary key is updated
     *
     * @var boolean
     */
    private $primary_key_updated = false;

    /**
     * Validate object properties before object is saved
     *
     * This method is called before the item is saved and can be used to fetch
     * errors in data before we really save it database. $errors is instance of
     * ValidationErrors class that is used for error collection. If collection
     * is empty object is considered valid and save process will continue
     *
     * @param Validator $validator
     */
    public function validate(Validator &$validator)
    {
        $this->triggerEvent('on_validate', [ &$validator ]);
    }

    /**
     * Returns true if $var is the same object this object is
     *
     * Comparison is done on class - PK values for loaded objects, or as simple
     * object comparison in case objects are not saved and loaded
     *
     * @param DataObject|mixed $var
     * @return boolean
     */
    public function is(&$var)
    {
        if ($var instanceof DataObject) {
            if ($this->isLoaded()) {
                return $var->isLoaded() && get_class($this) == get_class($var) && $this->getPrimaryKeyValue() == $var->getPrimaryKeyValue();
            } else {
                foreach ($this->getFields() as $field_name) {
                    if (!$var->fieldExists($field_name) || $this->getFieldValue($field_name) !== $var->getFieldValue($field_name)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Return object attributes
     *
     * This function will return array of attribute name -> attribute value pairs
     * for this specific project
     *
     * @return array
     */
    public function getAttributes()
    {
        $field_values = [];

        foreach ($this->fields as $field) {
            $field_values[$field] = $this->getFieldValue($field);
        }

        return $field_values;
    }

    /**
     * Set object attributes / properties. This function will take hash and set
     * value of all fields that she finds in the hash
     *
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        if (empty($attributes)) {
            $attributes = [];
        }

        $this->triggerEvent('on_set_attributes', [ &$attributes ]);

        foreach ($attributes as $k => $v) {
            if (is_array($this->protect) && (in_array($k, $this->protect) || in_array($k, $this->protect))) {
                continue; // field is in list of protected fields
            }
            if (is_array($this->accept) && !(in_array($k, $this->accept) || in_array($k, $this->protect))) {
                continue; // not in list of acceptable fields
            }
            if ($this->fieldExists($k)) {
                $this->setFieldValue($k, $attributes[$k]);
            } else {
                $this->setAttribute($k, $v);
            }
        }
    }

    /**
     * Return primary key columns
     *
     * @return array
     */
    public function getPrimaryKey()
    {
        return $this->primary_key;
    }

    /**
     * Return value of primary key
     *
     * @return array
     */
    public function getPrimaryKeyValue()
    {
        if ($this->primary_key && count($this->primary_key)) {
            $ret = array();
            foreach ($this->primary_key as $pk) {
                $ret[$pk] = $this->getFieldValue($pk);
            }

            return count($ret) > 1 ? $ret : $ret[$this->primary_key[0]];
        }

        return null;
    }

    /**
     * Return value of table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    // ---------------------------------------------------
    //  CRUD methods
    // ---------------------------------------------------

    /**
     * Load object by specific ID
     *
     * @param  mixed             $id
     * @return boolean
     * @throws InvalidParamError
     */
    public function load($id)
    {
        if ($id) {
            $key = $this->getCacheKey(null, (integer) $id);

            $row = AngieApplication::cache()->isCached($key) ? AngieApplication::cache()->get($key) : null;

            if (empty($row)) {
                $fields = $this->getFields();
                $table_name = $this->getTableName();
                $where = $this->getWherePartById($id);

                $row = AngieApplication::cache()->get($key, function () use ($id, $fields, $table_name, $where) {
                    return DB::executeFirstRow("SELECT " . implode(', ', $fields) . " FROM $table_name WHERE $where  LIMIT 0, 1");
                });
            }

            if (is_array($row)) {
                return $this->loadFromRow($row);
            } else {
                return false;
            }
        } else {
            throw new InvalidParamError('id', $id, '$id is expected to be a valid object ID');
        }
    }

    /**
     * Load data from database row
     *
     * If $cache_row is set to true row data will be added to cache
     *
     * @param  array             $row
     * @param  boolean           $cache_row
     * @return boolean
     * @throws InvalidParamError
     */
    public function loadFromRow($row, $cache_row = false)
    {
        if ($row && is_array($row)) {
            $this->is_loading = true;

            foreach ($row as $k => $v) {
                if ($this->fieldExists($k)) {
                    $this->setFieldValue($k, $v);
                }
            }

            if ($cache_row) {
                AngieApplication::cache()->set($this->getCacheKey(null, (integer) $row['id']), $row);
            }

            $this->setLoaded(true);
            $this->is_loading = false;
            $this->resetModifiedFlags();
        } else {
            $this->is_loading = false;
            throw new InvalidParamError('row', $row, '$row is expected to be loaded database row');
        }

        return true;
    }

    /**
     * Save object into database (insert or update)
     *
     * @throws ValidationException
     */
    public function save()
    {
        if ($this->isNew()) {
            foreach ($this->default_field_values as $field_name => $field_value) {
                if (empty($this->values[$field_name]) && !array_key_exists($field_name, $this->values)) {
                    $this->setFieldValue($field_name, $field_value);
                }
            }
        }

        $validator = new Validator($this->values);

        $this->validate($validator);

        if ($validator->hasErrors()) {
            throw $validator->createException();
        }

        if ($this->isNew()) {
            $this->doInsert();
        } else {
            $this->doUpdate();
        }

//        AngieApplication::cache()->removeByObject($this);
    }

    /**
     * Delete specific object (and related objects if neccecery)
     *
     * @param boolean $bulk
     */
    public function delete($bulk = false)
    {
        if ($this->isLoaded()) {
//            $cache_id = $this->getCacheKey();

            $this->triggerEvent('on_before_delete', [ $bulk ]);

//            Angie\Events::trigger('on_before_object_deleted', [ &$this, $bulk ]);

            $this->connection->delete($this->table_name, $this->getWherePartById($this->getPrimaryKeyValue()));

//            DB::execute("DELETE FROM " . $this->getTableName() . " WHERE " . $this->getWherePartById($this->getPrimaryKeyValue()));

            $this->is_new = true;

//            AngieApplication::cache()->remove($cache_id);

            $this->triggerEvent('on_after_delete', [ $bulk ]);

//            Angie\Events::trigger('on_object_deleted', [ &$this, $bulk ]);
        }
    }

    /**
     * Create a copy of this object and optionally save it
     *
     * @param  boolean    $save
     * @return DataObject
     */
    public function copy($save = false)
    {
        $object_class = get_class($this);

        /**
         * @var DataObject $copy
         */
        $copy = new $object_class();

        foreach ($this->fields as $field) {
            if (!in_array($field, $this->primary_key)) {
                $copy->setFieldValue($field, $this->getFieldValue($field));
            }
        }

        if ($save) {
            $copy->save();
        }

        return $copy;
    }

    // ---------------------------------------------------
    //  Flags
    // ---------------------------------------------------

    /**
     * Return value of $is_new variable
     *
     * @return boolean
     */
    public function isNew()
    {
        return (boolean) $this->is_new;
    }

    /**
     * Set new stamp value
     *
     * @param boolean $value New value
     */
    public function setNew($value)
    {
        $this->is_new = (boolean) $value;
    }

    /**
     * Returns true if this object have row in database
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return !$this->is_new;
    }

    /**
     * Set loaded stamp value
     *
     * @param boolean $value New value
     */
    public function setLoaded($value)
    {
        $this->is_new = !$value;
    }

    /**
     * Returns true if this object is in the middle of hydration process
     * (loading values from database row)
     *
     * @return boolean
     */
    public function isLoading()
    {
        return $this->is_loading;
    }

//    // ---------------------------------------------------
//    //  Etag
//    // ---------------------------------------------------
//
//    /**
//     * Return true if this object can be tagged and cached on client side
//     *
//     * @return bool|null
//     */
//    public function canBeTagged()
//    {
//        return $this->fieldExists('updated_on');
//    }
//
//    /**
//     * Cached tag value
//     *
//     * @var string
//     */
//    private $tag = false;
//
//    /**
//     * Return collection etag
//     *
//     * @param  IUser   $user
//     * @param  boolean $use_cache
//     * @return string
//     */
//    public function getTag(IUser $user, $use_cache = true)
//    {
//        if ($this->canBeTagged() && ($this->tag === false || empty($use_cache))) {
//            $timestamp = $this->getFieldValue('updated_on') instanceof DateTimeValue ? $this->getFieldValue('updated_on')->toMySQL() : '-- unknown --';
//
//            $this->tag = '"' . implode(',', [
//            APPLICATION_VERSION,
//            'object',
//            $this->getModelName(),
//            $this->getId(),
//            $user->getEmail(),
//            sha1(APPLICATION_UNIQUE_KEY . $timestamp),
//            ]) . '"';
//        }
//
//        return $this->tag;
//    }

    // ---------------------------------------------------
    //  Fields
    // ---------------------------------------------------

    /**
     * Return object ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->getFieldValue('id');
    }

    /**
     * Set value of id field
     *
     * @param  integer $value
     * @return $this
     */
    public function &setId($value)
    {
        $this->setFieldValue('id', $value);

        return $this;
    }

    /**
     * Check if specific field is defined
     *
     * @param  string  $field Field name
     * @return boolean
     */
    public function fieldExists($field)
    {
        return in_array($field, $this->fields);
    }

    /**
     * Return array of modified fields
     *
     * @return array
     */
    public function getModifiedFields()
    {
        return $this->modified_fields;
    }

    /**
     * Check if this object has modified columns
     *
     * @return boolean
     */
    public function isModified()
    {
        return (boolean) count($this->modified_fields);
    }

    /**
     * Returns true if specific field is modified
     *
     * @param  string  $field
     * @return boolean
     */
    public function isModifiedField($field)
    {
        return in_array($field, $this->modified_fields);
    }

    /**
     * Revert field to old value
     *
     * @param $field
     */
    public function revertField($field)
    {
        if ($this->isModifiedField($field)) {
            $this->setFieldValue($field, $this->getOldFieldValue($field)); // revert field value

            if (($key = array_search($field, $this->modified_fields)) !== false) {
                unset($this->modified_fields[$field]); // remove modified flag
            }
        }
    }

    /**
     * Check if selected field is primary key
     *
     * @param  string  $field Field that need to be checked
     * @return boolean
     */
    public function isPrimaryKey($field)
    {
        return in_array($field, $this->primary_key);
    }

    /**
     * Return list of fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Calculate fields checksum
     *
     * @return string
     */
    public function getFieldsChecksum()
    {
        return md5(implode(' ', $this->values));
    }

    /**
     * Return value of specific field and typecast it...
     *
     * @param  string $field   Field value
     * @param  mixed  $default Default value that is returned in case of any error
     * @return mixed
     */
    public function getFieldValue($field, $default = null)
    {
        if (empty($this->values[$field]) && !array_key_exists($field, $this->values)) {
            return empty($this->default_field_values[$field]) && !array_key_exists($field, $this->default_field_values) ? $default : $this->default_field_values[$field];
        } else {
            return $this->values[$field];
        }
    }

    /**
     * Return old field values, before fields were updated
     *
     * @return array
     */
    public function getOldValues()
    {
        return $this->old_values;
    }

    /**
     * Return all field value
     *
     * @param string $field
     * @return mixed
     */
    public function getOldFieldValue($field)
    {
        return isset($this->old_values[$field]) ? $this->old_values[$field] : null;
    }

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
    public function setFieldValue($field, $value)
    {
        if (in_array($field, $this->fields)) {
            if ($field === 'id') {
                $value = $value === null ? null : (integer) $value;
            }

            if ($value === null && array_key_exists($field, $this->default_field_values)) {
                throw new InvalidArgumentException("Value of '$field' can't be null");
            }

            if (!$this->isLoading()) {
                $this->triggerEvent('on_prepare_field_value_before_set', [ $field, &$value ]);
            }

            if (!array_key_exists($field, $this->values) || ($this->values[$field] !== $value)) {

                // If we are loading object there is no need to remember if this field
                // was modified, if PK has been updated and old value. We just skip that
                if (!$this->is_loading) {
                    if (isset($this->values[$field])) {
                        $old_value = $this->values[$field]; // Remember old value
                    }

                    // Save primary key value. Also make sure that only the first PK value is
                    // saved as old. Not to save second value on third modification ;)
                    if ($this->isPrimaryKey($field) && !isset($this->primary_key_updated[$field])) {
                        if (!is_array($this->primary_key_updated)) {
                            $this->primary_key_updated = array();
                        }
                        $this->primary_key_updated[$field] = true;
                    }

                    // Save old value if we haven't done that already
                    if (isset($old_value) && !isset($this->old_values[$field])) {
                        $this->old_values[$field] = $old_value;
                    }

                    $this->addModifiedField($field); // Remember that this file was modified
                }

                $this->values[$field] = $value;
            }

            return $value;
        } else {
            throw new InvalidArgumentException("Field '$field' does not exist");
        }
    }

    /**
     * Add new modified field
     *
     * @param string $field Field that need to be added
     */
    public function addModifiedField($field)
    {
        if (!in_array($field, $this->modified_fields)) {
            $this->modified_fields[] = $field;
        }
    }

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        $this->triggerEvent('on_set_attribute', [ $attribute, $value ]);
    }

    // ---------------------------------------------------
    //  Database interaction
    // ---------------------------------------------------

    /**
     * Check if specific row exists in database
     *
     * @param  integer $id
     * @return boolean
     */
    public function exists($id)
    {
        return (boolean) $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM ' . $this->connection->escapeTableName($this->table_name) . ' WHERE ' . $this->getWherePartById($id));
    }

    /**
     * Insert record in the database
     */
    private function doInsert()
    {
        $last_insert_id = $this->connection->insert($this->table_name, $this->values);

//        DB::execute($this->getInsertSQL());

        if (($this->auto_increment !== null) && (!isset($this->values[$this->auto_increment]) || !$this->values[$this->auto_increment])) {
            $this->values[$this->auto_increment] = $last_insert_id;
        }
        $this->resetModifiedFlags();
        $this->setLoaded(true);
    }

    /**
     * Update database record
     */
    private function doUpdate()
    {
        if (count($this->modified_fields)) {
            $updates = [];

            foreach ($this->modified_fields as $modified_field) {
                $updates[$modified_field] = $this->values[$modified_field];
            }

            if (is_array($this->primary_key_updated)) {
//                $pks = $this->getPrimaryKey();
//                $old = [];
//
//                foreach ($pks as $pk) {
//                    $old[$pk] = isset($this->old_values[$pk]) ? $this->old_values[$pk] : $this->getFieldValue($pk);
//                }
//
//                if (count($old) && $this->exists($old)) {
//                    return sprintf("UPDATE %s SET %s WHERE %s", $this->getTableName(), implode(', ', $fields), $this->getWherePartById($old));
//                } else {
//                    return $this->getInsertSQL();
//                }
            } else {
                $this->connection->update($this->table_name, $updates, $this->getWherePartById($this->getPrimaryKeyValue()));

//                return sprintf("UPDATE %s SET %s WHERE %s", $this->getTableName(), implode(', ', $fields), $this->getWherePartById($this->getPrimaryKeyValue()));
            }
        }


        // ---------------------------------------------------
        //  Old
        // ---------------------------------------------------

        $sql = $this->getUpdateSQL();

        if ($sql) {
            DB::execute($sql);

            $this->resetModifiedFlags();
            $this->setLoaded(true);
        }
    }

    /**
     * Prepare insert query
     *
     * @return string
     */
    public function getInsertSQL()
    {
        $fields = $values = [];

        foreach ($this->values as $field_name => $field_value) {
            if ($this->fieldExists($field_name)) {
                $fields[] = $field_name;
                $values[] = DB::escape($field_value);
            }
        }

        return 'INSERT INTO ' . $this->getTableName() . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
    }

    /**
     * Prepare update query
     *
     * @return string
     */
    public function getUpdateSQL()
    {
        $fields = [];

        if (!count($this->modified_fields)) {
            return null;
        }

        foreach ($this->fields as $field_name) {
            if ($this->isModifiedField($field_name)) {
                $fields[] = $field_name . ' = ' . DB::escape($this->values[$field_name]);
            }
        }

        if (is_array($this->primary_key_updated)) {
            $pks = $this->getPrimaryKey();
            $old = [];

            foreach ($pks as $pk) {
                $old[$pk] = isset($this->old_values[$pk]) ? $this->old_values[$pk] : $this->getFieldValue($pk);
            }

            if (count($old) && $this->exists($old)) {
                return sprintf("UPDATE %s SET %s WHERE %s", $this->getTableName(), implode(', ', $fields), $this->getWherePartById($old));
            } else {
                return $this->getInsertSQL();
            }
        } else {
            return sprintf("UPDATE %s SET %s WHERE %s", $this->getTableName(), implode(', ', $fields), $this->getWherePartById($this->getPrimaryKeyValue()));
        }
    }

    /**
     * Return where part of query
     *
     * @param  mixed  $value Array of values if we need them
     * @return string
     */
    public function getWherePartById($value = null)
    {
        $pks = $this->getPrimaryKey();

        if (count($pks) > 1) {
            $where = array();
            foreach ($pks as $field) {
                $field_value = isset($value[$field]) ? $value[$field] : $this->getFieldValue($field);
                $where[] = $field . ' = ' . DB::escape($field_value);
            }

            return count($where) > 1 ? implode(' AND ', $where) : $where[0];
        } else {
            $pk = $pks[0];
            $pk_value = is_array($value) ? $value[$pk] : $value;

            return $pk . ' = ' . DB::escape($pk_value);
        }
    }

    /**
     * Reset modification idicators
     *
     * Useful when you use setXXX functions but you dont want to modify
     * anything (just loading data from database in fresh object using
     * setFieldValue function)
     */
    public function resetModifiedFlags()
    {
        $this->modified_fields = $this->old_values = [];
        $this->primary_key_updated = false;
    }

    /**
     * Return cache key for this object)
     *
     * If we still don't have a lodaded object, we can pass a known ID to get the cache key
     *
     * @param  array   $subnamespace
     * @param  integer $id
     * @return array
     */
    public function getCacheKey($subnamespace = null, $id = null)
    {
        if ($id === null) {
            return get_data_object_cache_key($this->getModelName(true), $this->getId(), $subnamespace);
        } else {
            return get_data_object_cache_key($this->getModelName(true), $id, $subnamespace);
        }
    }

    // ---------------------------------------------------
    //  Events
    // ---------------------------------------------------

    /**
     * Registered event handlers
     *
     * @var array
     */
    private $event_handlers = [];

    /**
     * Register an internal event handler
     *
     * @param $event
     * @param $handler
     * @throws InvalidParamError
     */
    protected function registerEventHandler($event, $handler)
    {
        if (AngieApplication::isInDevelopment()) {
            if (empty($this->event_handlers[$event])) {
                $this->event_handlers[$event] = [];
            }

            if ($handler instanceof Closure) {
                $this->event_handlers[$event][] = $handler;
            } elseif (method_exists($this, $handler)) {
                $reflection = new ReflectionMethod($this, $handler);

                if ($reflection->isPublic()) {
                    $this->event_handlers[$event][] = $handler;
                } else {
                    throw new InvalidParamError('handler', $handler, 'Handler "' . $handler . '" is not a public method. To use private methods, put them in closures');
                }
            } else {
                throw new InvalidParamError('handler', $handler, 'Even handler can be an existing method name or a Closure instance');
            }
        } else {
            $this->event_handlers[$event][] = $handler;
        }
    }

    /**
     * Trigger an internal event
     *
     * @param string $event
     * @param array  $event_parameters
     */
    protected function triggerEvent($event, $event_parameters = null)
    {
        if (isset($this->event_handlers[$event])) {
            foreach ($this->event_handlers[$event] as $handler) {
                if (empty($event_parameters)) {
                    $event_parameters = [];
                }

                if ($handler instanceof Closure) {
                    call_user_func_array($handler, $event_parameters);
                } else {
                    call_user_func_array([ $this, $handler ], $event_parameters);
                }
            }
        }
    }

    // ---------------------------------------------------
    //  Describe
    // ---------------------------------------------------

    /**
     * Return array or property => value pairs that describes this object
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $result = [
        'id' => $this->getId(),
        'class' => get_class($this),
        'url_path' => $this instanceof IRoutingContext && $this->isLoaded() ? $this->getUrlPath() : '#',
        ];

//        if ($this->fieldExists('name')) {
//            $result['name'] = $this->getName();
//        }

        $this->triggerEvent('on_json_serialize', [ &$result ]);

        return $result;
    }

    /**
     * Describe single
     *
     * @param  array $result
     */
    public function describeSingleForFeather(array &$result)
    {
        $this->triggerEvent('on_describe_single', [ &$result ]);
    }

    // ---------------------------------------------------
    //  Touch
    // ---------------------------------------------------

    /**
     * Is this object untouchable
     *
     * @var bool
     */
    private $is_untouchable = false;

    /**
     * Run $callback while this object is untouchable
     *
     * @param callable $callback
     */
    public function untouchable(callable $callback)
    {
        $original_untouchable = $this->is_untouchable;

        $this->is_untouchable = true;

        call_user_func($callback);

        $this->is_untouchable = $original_untouchable;
    }

    /**
     * Refresh object's updated_on flag
     *
     * @param User|null  $by
     * @param null|array $additional
     * @param bool       $save
     */
    public function touch($by = null, $additional = null, $save = true)
    {
        if ($this->is_untouchable) {
            return;
        }

        $this->triggerEvent('on_before_touch', [ $by, $additional, $save ]);

        if ($this instanceof IUpdatedBy && $by instanceof IUser) {
            $this->setUpdatedBy($by);
        }

        if ($this instanceof IUpdatedOn) {
            $this->setUpdatedOn(DateTimeValue::now());
        }

        if ($save) {
            $this->save();
        }

        $this->triggerEvent('on_after_touch', [ $by, $additional, $save ]);
    }

    /**
     * Return a list of properties that are watched
     *
     * @return array
     */
    public function touchParentOnPropertyChange()
    {
        return false;
    }

    // ---------------------------------------------------------------
    //  Validators
    // ---------------------------------------------------------------

    /**
     * Validates presence of specific field
     *
     * In case of string value is trimmed and compared with the empty string. In
     * case of any other type empty() function is used. If $min_value argument is
     * provided value will also need to be larger or equal to it
     * (validateMinValueOf validator is used)
     *
     * @param  string  $field     Field name
     * @param  mixed   $min_value
     * @param  Closure $modifier
     * @return boolean
     */
    public function validatePresenceOf($field, $min_value = null, $modifier = null)
    {
        $value = $this->getFieldValue($field);

        if ($modifier && ($modifier instanceof Closure || function_exists($modifier))) {
            $value = call_user_func($modifier, $value);
        }

        if (is_string($value)) {
            if (trim($value)) {
                return $min_value === null ? true : $this->validateMinValueOf($field, $min_value);
            } else {
                return false;
            }
        } else {
            if (empty($value)) {
                return false;
            } else {
                return $min_value === null ? true : $this->validateMinValueOf($field, $min_value);
            }
        }
    }

    /**
     * This validator will return true if $value is unique (there is no row with such value in that field)
     *
     * @param  string  $field
     * @return boolean
     */
    public function validateUniquenessOf($field)
    {
        // Don't do COUNT(*) if we have one PK column
        $escaped_pk = is_array($pk_fields = $this->getPrimaryKey()) ? '*' : $pk_fields;

        // Get columns
        $fields = func_get_args();
        if (!is_array($fields) || count($fields) < 1) {
            return true;
        }

        // Check if we have existsing columns
        foreach ($fields as $field) {
            if (!$this->fieldExists($field)) {
                return false;
            }
        }

        // Get where parets
        $where_parts = array();
        foreach ($fields as $field) {
            $where_parts[] = $field . ' = ' . DB::escape($this->values[$field]);
        }

        // If we have new object we need to test if there is any other object
        // with this value. Else we need to check if there is any other EXCEPT
        // this one with that value
        if ($this->isNew()) {
            $sql = sprintf("SELECT COUNT($escaped_pk) AS 'row_count' FROM %s WHERE %s", $this->getTableName(), implode(' AND ', $where_parts));
        } else {

            // Prepare PKs part...
            $pks = $this->getPrimaryKey();
            $pk_values = array();
            if (is_array($pks)) {
                foreach ($pks as $pk) {
                    if (isset($this->primary_key_updated[$pk]) && $this->primary_key_updated[$pk]) {
                        $primary_key_value = $this->old_values[$pk];
                    } else {
                        $primary_key_value = $this->values[$pk];
                    }
                    $pk_values[] = sprintf('%s <> %s', $pk, DB::escape($primary_key_value));
                }
            }

            // Prepare SQL
            $sql = sprintf("SELECT COUNT($escaped_pk) AS 'row_count' FROM %s WHERE (%s) AND (%s)", $this->getTableName(), implode(' AND ', $where_parts), implode(' AND ', $pk_values));
        }

        return DB::executeFirstCell($sql) < 1;
    }

    /**
     * Validate max value of specific field. If that field is string time
     * max lenght will be validated
     *
     * @param  string  $field
     * @param  integer $max
     * @return boolean
     */
    public function validateMaxValueOf($field, $max)
    {
        if ($this->fieldExists($field)) {
            $value = $this->getFieldValue($field);

            if (is_string($value) && !is_numeric($value)) {
                return strlen(trim($value)) <= $max;
            } else {
                return $value <= $max;
            }
        } else {
            return false;
        }
    }

    /**
     * Valicate minimal value of specific field.
     *
     * If string minimal lenght is checked (string is trimmed before it is
     * compared). In any other case >= operator is used
     *
     * @param  string  $field
     * @param  integer $min   Minimal value
     * @return boolean
     */
    public function validateMinValueOf($field, $min)
    {
        if ($this->fieldExists($field)) {
            $value = $this->getFieldValue($field);

            if (is_string($value) && !is_numeric($value)) {
                return strlen_utf(trim($value)) >= $min;
            } else {
                return $value >= $min;
            }
        }

        return false;
    }

    /**
     * Valicate that field value is in range of $min and $max
     *
     * If field value is string, lenght is checked (string is trimmed before it is
     * compared). In any other case <= and >= operator is used
     *
     * @param  string  $field
     * @param  integer $min
     * @param  integer $max
     * @return boolean
     */
    public function validateValueInRange($field, $min, $max)
    {
        if ($this->fieldExists($field)) {
            $value = $this->getFieldValue($field);

            if (is_string($value) && !is_numeric($value)) {
                $string_length = strlen_utf(trim($value));

                return $string_length >= $min && $string_length <= $max;
            } else {
                return $value >= $min && $value <= $max;
            }
        }

        return false;
    }
}