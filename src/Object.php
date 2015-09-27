<?php

namespace ActiveCollab\DatabaseObject;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseObject\Exception\ValidationException;
use InvalidArgumentException;
use LogicException;

/**
 * @package ActiveCollab\DatabaseObject
 */
abstract class Object implements ObjectInterface
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * Name of the table
     *
     * @var string
     */
    protected $table_name;

    /**
     * Primary key fields
     *
     * @var array
     */
    protected $primary_key = 'id';

    /**
     * Name of autoincrement field (if exists)
     *
     * @var string
     */
    protected $auto_increment = 'id';

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
     * @param PoolInterface       $pool
     * @param ConnectionInterface $connection
     */
    public function __construct(PoolInterface $pool, ConnectionInterface $connection)
    {
        $this->pool = $pool;
        $this->connection = $connection;

        if ($traits = $pool->getTraitNamesByType(get_class($this))) {
            foreach ($traits as $trait) {
                $trait_constructor = str_replace('\\', '', $trait);

                if (method_exists($this, $trait_constructor)) {
                    $this->$trait_constructor();
                }
            }
        }
    }

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
    private $primary_key_modified = false;

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
    public function validate(ValidatorInterface &$validator)
    {
        $this->triggerEvent('on_validate', [&$validator]);
    }

    /**
     * Returns true if $var is the same object this object is
     *
     * Comparison is done on class - PK values for loaded objects, or as simple
     * object comparison in case objects are not saved and loaded
     *
     * @param  Object  $var
     * @return boolean
     */
    public function is(&$var)
    {
        if ($var instanceof ObjectInterface) {
            if ($this->isLoaded()) {
                return $var->isLoaded() && get_class($this) == get_class($var) && $this->getId() == $var->getId();
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
     * Return primary key columns
     *
     * @return array
     */
    public function getPrimaryKey()
    {
        return $this->primary_key;
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
     * Load data from database row
     *
     * If $cache_row is set to true row data will be added to cache
     *
     * @param  array                    $row
     * @throws InvalidArgumentException
     */
    public function loadFromRow(array $row)
    {
        if ($row && is_array($row)) {
            $this->startLoading();

            foreach ($row as $k => $v) {
                if ($this->fieldExists($k)) {
                    $this->setFieldValue($k, $v);
                }
            }

            $this->doneLoading();
        } else {
            throw new InvalidArgumentException('$row is expected to be loaded database row');
        }
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

        $is_new = $this->isNew();

        $modifications = [];

        if (count($this->getModifiedFields())) {
            foreach ($this->getModifiedFields() as $field) {
                $old_value = $this->getOldFieldValue($field);
                $new_value = $this->getFieldValue($field);

                if ($old_value != $new_value) {
                    $modifications[$field] = [$this->getOldFieldValue($field), $this->getFieldValue($field)];
                }
            }
        }

        $this->triggerEvent('on_before_save', [$is_new, $modifications]);

        if ($this->isNew()) {
            $this->insert();
        } else {
            $this->update();
        }

        $this->triggerEvent('on_after_save', [$is_new, $modifications]);
    }

    /**
     * Delete specific object (and related objects if neccecery)
     *
     * @param boolean $bulk
     */
    public function delete($bulk = false)
    {
        if ($this->isLoaded()) {
            $this->connection->transact(function() use ($bulk) {
                $this->triggerEvent('on_before_delete', [ $bulk ]);

                $this->connection->delete($this->table_name, $this->getWherePartById($this->getId()));
                $this->is_new = true;

                $this->triggerEvent('on_after_delete', [ $bulk ]);
            });
        }
    }

    /**
     * Create a copy of this object and optionally save it
     *
     * @param  boolean $save
     * @return Object
     */
    public function copy($save = false)
    {
        $object_class = get_class($this);

        /**
         * @var Object $copy
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
     * Returns true if this object have row in database
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return !$this->is_new;
    }

    /**
     * Mark start of loading from row
     */
    private function startLoading()
    {
        $this->is_loading = true;
    }

    /**
     * Done loading from row
     */
    private function doneLoading()
    {
        if ($this->is_loading) {
            $this->is_loading = false;
        }

        $this->setAsLoaded();
    }

    /**
     * Set loaded stamp value
     */
    private function setAsLoaded()
    {
        $this->is_new = false;
        $this->resetModifiedFlags();
    }

    /**
     * Returns true if this object is in the middle of hydration process
     * (loading values from database row)
     *
     * @return boolean
     */
    private function isLoading()
    {
        return $this->is_loading;
    }

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
     * Return true if primary key is modified
     *
     * @return bool
     */
    public function isPrimaryKeyModified()
    {
        return $this->primary_key_modified;
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
        return $field === 'id';
    }

    /**
     * Return list of fields
     */
    public function getFields()
    {
        return $this->fields;
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
     * @return $this
     * @throws InvalidArgumentException
     */
    public function &setFieldValue($field, $value)
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
                if (!$this->isLoading()) {
                    if (isset($this->values[$field])) {
                        $old_value = $this->values[$field]; // Remember old value
                    }

                    // Save primary key value. Also make sure that only the first PK value is
                    // saved as old. Not to save second value on third modification ;)
                    if ($this->isPrimaryKey($field) && !$this->primary_key_modified) {
                        $this->primary_key_modified = true;
                    }

                    // Save old value if we haven't done that already
                    if (isset($old_value) && !isset($this->old_values[$field])) {
                        $this->old_values[$field] = $old_value;
                    }

                    // Remember that this field is modified
                    if (!in_array($field, $this->modified_fields)) {
                        $this->modified_fields[] = $field;
                    }
                }

                $this->values[$field] = $value;
            }
        } else {
            throw new InvalidArgumentException("Field '$field' does not exist");
        }

        return $this;
    }

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        $this->triggerEvent('on_set_attribute', [$attribute, $value]);
    }

    // ---------------------------------------------------
    //  Database interaction
    // ---------------------------------------------------

    /**
     * Insert record in the database
     */
    private function insert()
    {
        $last_insert_id = $this->connection->insert($this->table_name, $this->values);

        if (empty($this->values[$this->auto_increment])) {
            $this->values[$this->auto_increment] = $last_insert_id;
        }

        $this->setAsLoaded();
    }

    /**
     * Update database record
     */
    private function update()
    {
        if (count($this->modified_fields)) {
            $updates = [];

            foreach ($this->modified_fields as $modified_field) {
                $updates[$modified_field] = $this->values[$modified_field];
            }

            if ($this->primary_key_modified) {
                $old_id = isset($this->old_values['id']) ? $this->old_values['id'] : $this->getId();

                if ($this->pool->exists(get_class($this), $this->getId())) {
                    throw new LogicException("Object #" . $this->getId() . " can't be overwritten");
                } else {
                    $this->connection->update($this->table_name, $updates, $this->getWherePartById($old_id));
                }
            } else {
                $this->connection->update($this->table_name, $updates, $this->getWherePartById($this->getId()));
            }

            $this->setAsLoaded();
        }
    }

    /**
     * Return where part of query
     *
     * @param  integer $id
     * @return string
     */
    private function getWherePartById($id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException("Value '$id' is not a valid ID");
        }

        return $this->connection->prepare('(`id` = ?)', $id);
    }

    /**
     * Reset modification idicators
     *
     * Useful when you use setXXX functions but you dont want to modify
     * anything (just loading data from database in fresh object using
     * setFieldValue function)
     */
    private function resetModifiedFlags()
    {
        $this->modified_fields = $this->old_values = [];
        $this->primary_key_modified = false;
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
     * @param string   $event
     * @param callable $handler
     */
    protected function registerEventHandler($event, callable $handler)
    {
        if (empty($event)) {
            throw new InvalidArgumentException('Event name is required');
        }

        if (is_callable($handler)) {
            if (empty($this->event_handlers[$event])) {
                $this->event_handlers[$event] = [];
            }

            $this->event_handlers[$event][] = $handler;
        } else {
            throw new InvalidArgumentException('Handler not callable');
        }
    }

    /**
     * Trigger an internal event
     *
     * @param string     $event
     * @param array|null $event_parameters
     */
    protected function triggerEvent($event, array $event_parameters = null)
    {
        if (isset($this->event_handlers[$event])) {
            if (empty($event_parameters)) {
                $event_parameters = [];
            }

            foreach ($this->event_handlers[$event] as $handler) {
                call_user_func_array($handler, $event_parameters);
            }
        }
    }

    /**
     * Return array or property => value pairs that describes this object
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $result = ['id' => $this->getId(), 'type' => get_class($this)];
        $this->triggerEvent('on_json_serialize', [&$result]);
        return $result;
    }
}