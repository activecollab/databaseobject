<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\DatabaseObject\Entity;

use ActiveCollab\ContainerAccess\ContainerAccessInterface;
use ActiveCollab\ContainerAccess\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DatabaseObject\Exception\ValidationException;
use ActiveCollab\DatabaseObject\Pool;
use ActiveCollab\DatabaseObject\PoolInterface;
use ActiveCollab\DatabaseObject\Validator;
use ActiveCollab\DatabaseObject\ValidatorInterface;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\DateValue\DateTimeValueInterface;
use ActiveCollab\DateValue\DateValue;
use ActiveCollab\DateValue\DateValueInterface;
use DateTime;
use Doctrine\Common\Inflector\Inflector;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseObject
 */
abstract class Entity implements EntityInterface, ContainerAccessInterface
{
    use ContainerAccessInterfaceImplementation;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Name of the table.
     *
     * @var string
     */
    protected $table_name;

    /**
     * Primary key fields.
     *
     * @var array
     */
    protected $primary_key = 'id';

    /**
     * Name of autoincrement field (if exists).
     *
     * @var string
     */
    protected $auto_increment = 'id';

    /**
     * @var string[]
     */
    protected $order_by = ['id'];

    /**
     * Table fields that are managed by this entity.
     */
    protected $entity_fields = [];

    /**
     * Generated fields that are loaded, but not managed by the entity.
     *
     * @var array
     */
    protected $generated_entity_fields = [];

    /**
     * List of default field values.
     *
     * @var array
     */
    protected $default_entity_field_values = [];

    public function __construct(ConnectionInterface $connection, PoolInterface $pool, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->pool = $pool;
        $this->logger = $logger;

        if ($traits = $pool->getTraitNamesByType(get_class($this))) {
            foreach ($traits as $trait) {
                $trait_constructor = str_replace('\\', '', $trait);

                if (method_exists($this, $trait_constructor)) {
                    $this->$trait_constructor();
                }
            }
        }

        $this->configure();
    }

    /**
     * Execute post-construction configuration.
     */
    protected function configure()
    {
    }

    // ---------------------------------------------------
    //  Internals, not overridable
    // ---------------------------------------------------

    /**
     * Indicates if this is new object (not saved).
     *
     * @var bool
     */
    private $is_new = true;

    /**
     * This flag is set to true when data from row are inserted into fields.
     *
     * @var bool
     */
    private $is_loading = false;

    /**
     * Field values.
     *
     * @var array
     */
    private $values = [];

    /**
     * Array of modified field values.
     *
     * Elements of this array are populated on setter call. Real name is
     * resolved, old value is saved here (if exists) and new one is set. Keys
     * used in this array are real field names only!
     *
     * @var array
     */
    private $old_values = [];

    /**
     * Array of modified fiels.
     *
     * @var array
     */
    private $modified_fields = [];

    /**
     * @var array
     */
    private $modified_attributes = [];

    /**
     * Primary key is updated.
     *
     * @var bool
     */
    private $primary_key_modified = false;

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
    public function validate(ValidatorInterface &$validator)
    {
        $this->triggerEvent('on_validate', [&$validator]);
    }

    /**
     * {@inheritdoc}
     */
    public function is($object)
    {
        if ($object instanceof EntityInterface) {
            if ($this->isLoaded()) {
                return $object->isLoaded() && get_class($this) == get_class($object) && $this->getId() == $object->getId();
            } else {
                foreach ($this->getEntityFields() as $field_name) {
                    if (!$object->entityFieldExists($field_name) ||
                        !$this->areFieldValuesSame(
                            $this->getFieldValue($field_name),
                            $object->getFieldValue($field_name)
                        )) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Return true if field values match.
     *
     * @param  mixed $value_1
     * @param  mixed $value_2
     * @return bool
     */
    private function areFieldValuesSame($value_1, $value_2)
    {
        if (($value_1 instanceof DateValueInterface && $value_2 instanceof DateValueInterface)
            || ($value_1 instanceof DateTimeValueInterface && $value_2 instanceof DateTimeValueInterface)) {
            return $value_1->getTimestamp() == $value_2->getTimestamp();
        } else {
            return $value_1 === $value_2;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey()
    {
        return $this->primary_key;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    // ---------------------------------------------------
    //  CRUD methods
    // ---------------------------------------------------

    /**
     * Load data from database row.
     *
     * If $cache_row is set to true row data will be added to cache
     *
     * @param  array                    $row
     * @throws InvalidArgumentException
     */
    public function loadFromRow(array $row)
    {
        if (empty($row)) {
            throw new InvalidArgumentException('Database row expected');
        }

        $this->startLoading();

        $found_generated_fields = [];

        foreach ($row as $k => $v) {
            if ($this->isGeneratedField($k)) {
                $found_generated_fields[] = $k;
            } elseif ($this->entityFieldExists($k)) {
                $this->setFieldValue($k, $v);
            }
        }

        if (!empty($found_generated_fields)) {
            $generated_field_values = [];

            $value_caster = $this->getGeneratedFieldsValueCaster();

            if ($value_caster instanceof ValueCasterInterface) {
                $generated_field_values = array_intersect_key($row, array_flip($found_generated_fields));

                $value_caster->castRowValues($generated_field_values);
            }

            foreach ($generated_field_values as $k => $v) {
                $this->setFieldValue($k, $v);
            }
        }

        $this->doneLoading();
    }

    /**
     * Save object into database (insert or update).
     *
     * @return $this
     * @throws ValidationException
     */
    public function &save()
    {
        // ---------------------------------------------------
        //  Populate defaults
        // ---------------------------------------------------

        if ($this->isNew()) {
            foreach ($this->default_entity_field_values as $field_name => $field_value) {
                if (empty($this->values[$field_name]) && !array_key_exists($field_name, $this->values)) {
                    $this->setFieldValue($field_name, $field_value);
                }
            }
        }

        // ---------------------------------------------------
        //  Trigger on before save
        // ---------------------------------------------------

        $is_new = $this->isNew();
        $modifications = $this->getModifications();

        $this->triggerEvent('on_before_save', [$is_new, $modifications]);

        // ---------------------------------------------------
        //  Validate
        // ---------------------------------------------------

        $values_to_validate = $this->values;

        foreach ($this->entity_fields as $field_name) {
            if (empty($values_to_validate[$field_name]) && !array_key_exists($field_name, $values_to_validate)) {
                $values_to_validate[$field_name] = null;
            }
        }

        $validator = new Validator(
            $this->connection,
            $this->table_name,
            $this->getId(),
            $this->getOldFieldValue('id'),
            $values_to_validate
        );

        $this->validate($validator);

        if ($validator->hasErrors()) {
            throw $validator->createException();
        }

        // ---------------------------------------------------
        //  Do save
        // ---------------------------------------------------

        if ($this->isNew()) {
            $this->insert();
        } else {
            $this->update();
        }

        $this->triggerEvent('on_after_save', [$is_new, $modifications]);

        $this->pool->remember($this);

        return $this;
    }

    /**
     * Delete specific object (and related objects if neccecery).
     *
     * @param  bool  $bulk
     * @return $this
     */
    public function &delete($bulk = false)
    {
        if ($this->isLoaded()) {
            $this->connection->transact(function () use ($bulk) {
                $this->triggerEvent('on_before_delete', [$bulk]);

                $this->connection->delete($this->table_name, $this->getWherePartById($this->getId()));
                $this->is_new = true;

                $this->triggerEvent('on_after_delete', [$bulk]);
            });
        }

        return $this;
    }

    /**
     * Create a copy of this object and optionally save it.
     *
     * @param  bool   $save
     * @return object
     */
    public function copy($save = false)
    {
        $object_class = get_class($this);

        /** @var EntityInterface $copy */
        $copy = new $object_class($this->connection, $this->pool, $this->logger);

        foreach ($this->getEntityFields() as $field) {
            if ($this->isPrimaryKey($field)) {
                continue;
            }

            $copy->setFieldValue($field, $this->getFieldValue($field));
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
     * Return value of $is_new variable.
     *
     * @return bool
     */
    public function isNew()
    {
        return (bool) $this->is_new;
    }

    /**
     * Returns true if this object have row in database.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return !$this->is_new;
    }

    /**
     * Mark start of loading from row.
     */
    private function startLoading()
    {
        $this->is_loading = true;
    }

    /**
     * Done loading from row.
     */
    private function doneLoading()
    {
        if ($this->is_loading) {
            $this->is_loading = false;
        }

        $this->setAsLoaded();
    }

    /**
     * Set loaded stamp value.
     */
    private function setAsLoaded()
    {
        $this->is_new = false;
        $this->resetModifiedFlags();
    }

    /**
     * Returns true if this object is in the middle of hydration process
     * (loading values from database row).
     *
     * @return bool
     */
    protected function isLoading()
    {
        return $this->is_loading;
    }

    // ---------------------------------------------------
    //  Fields
    // ---------------------------------------------------

    /**
     * Return object ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->getFieldValue('id');
    }

    /**
     * Set value of id field.
     *
     * @param  int   $value
     * @return $this
     */
    public function &setId($value)
    {
        $this->setFieldValue('id', $value);

        return $this;
    }

    /**
     * Check if this object has modified columns.
     *
     * @return bool
     */
    public function isModified()
    {
        return !empty($this->modified_fields) || !empty($this->modified_attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getModifiedFields()
    {
        return $this->modified_fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getModifications()
    {
        $result = [];

        if (count($this->getModifiedFields())) {
            foreach ($this->getModifiedFields() as $field) {
                $old_value = $this->getOldFieldValue($field);
                $new_value = $this->getFieldValue($field);

                if ($old_value != $new_value) {
                    $result[$field] = [$this->getOldFieldValue($field), $this->getFieldValue($field)];
                }
            }
        }

        return $result;
    }

    /**
     * Returns true if specific field is modified.
     *
     * @param  string $field
     * @return bool
     */
    public function isModifiedField($field)
    {
        return in_array($field, $this->modified_fields);
    }

    /**
     * Return true if primary key is modified.
     *
     * @return bool
     */
    public function isPrimaryKeyModified()
    {
        return $this->primary_key_modified;
    }

    /**
     * @return array
     */
    public function getModifiedAttributes()
    {
        return $this->modified_attributes;
    }

    /**
     * Return true if $attribute is modified.
     *
     * @param  string $attribute
     * @return bool
     */
    public function isModifiedAttribute($attribute)
    {
        return in_array($attribute, $this->modified_attributes);
    }

    protected function recordModifiedAttribute($attribute)
    {
        if (!in_array($attribute, $this->modified_attributes)) {
            $this->modified_attributes[] = $attribute;
        }
    }

    /**
     * Revert field to old value.
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
     * Check if selected field is primary key.
     *
     * @param  string $field Field that need to be checked
     * @return bool
     */
    public function isPrimaryKey($field)
    {
        return $field === 'id';
    }

    /**
     * @deprecated Use getEntityFields() instead.
     */
    public function getFields()
    {
        return $this->getEntityFields();
    }

    public function getEntityFields(): array
    {
        return $this->entity_fields;
    }

    /**
     * @deprecated Use entityFieldExists() instead.
     */
    public function fieldExists($field)
    {
        return $this->entityFieldExists($field);
    }

    public function entityFieldExists(string $entity_field): bool
    {
        return in_array($entity_field, $this->entity_fields) || in_array($entity_field, $this->generated_entity_fields);
    }

    /**
     * {@inheritdoc}
     */
    public function getGeneratedFields()
    {
        return $this->generated_entity_fields;
    }

    /**
     * {@inheritdoc}
     */
    public function generatedFieldExists($field)
    {
        return in_array($field, $this->generated_entity_fields);
    }

    /**
     * {@inheritdoc}
     */
    public function isGeneratedField($field)
    {
        return $this->generatedFieldExists($field);
    }

    /**
     * @var ValueCasterInterface
     */
    private $generated_fields_value_caster;

    /**
     * @return ValueCasterInterface
     */
    private function getGeneratedFieldsValueCaster()
    {
        return $this->generated_fields_value_caster;
    }

    /**
     * Set generated fields value caster.
     *
     * @param  ValueCasterInterface|null $value_caster
     * @return $this
     */
    protected function &setGeneratedFieldsValueCaster(ValueCasterInterface $value_caster = null)
    {
        $this->generated_fields_value_caster = $value_caster;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldValue($field, $default = null)
    {
        if (empty($this->values[$field]) && !array_key_exists($field, $this->values)) {
            return empty($this->default_entity_field_values[$field])
                && !array_key_exists($field, $this->default_entity_field_values) ?
                    $default :
                    $this->default_entity_field_values[$field];
        } else {
            return $this->values[$field];
        }
    }

    /**
     * Return old field values, before fields were updated.
     *
     * @return array
     */
    public function getOldValues()
    {
        return $this->old_values;
    }

    /**
     * Return all field value.
     *
     * @param  string $field
     * @return mixed
     */
    public function getOldFieldValue($field)
    {
        return isset($this->old_values[$field]) ? $this->old_values[$field] : null;
    }

    /**
     * Set specific field value.
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
        if ($this->entityFieldExists($field)) {
            if ($field === 'id') {
                $value = $value === null ? null : (int) $value;
            }

            if ($value === null && array_key_exists($field, $this->default_entity_field_values)) {
                throw new InvalidArgumentException("Value of '$field' can't be null");
            }

            if (!$this->isLoading()) {
                $this->triggerEvent('on_prepare_field_value_before_set', [$field, &$value]);
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
     * Return a list of attributes that this object supports.
     *
     * @return array
     */
    protected function getAttributes()
    {
        return [];
    }

    /**
     * Return setter method name for the given attribute.
     *
     * @param  string $attribute
     * @return string
     */
    private function getAttributeSetter($attribute)
    {
        return 'set' . Inflector::classify($attribute);
    }

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls.
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @return $this
     */
    public function &setAttribute($attribute, $value)
    {
        if (in_array($attribute, $this->getAttributes())) {
            $setter = $this->getAttributeSetter($attribute);

            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        } else {
            $this->triggerEvent('on_set_attribute', [$attribute, $value]);
        }

        return $this;
    }

    /**
     * Use input $value and return a valid DateValue instance.
     *
     * @param  mixed          $value
     * @return DateValue|null
     */
    protected function getDateValueInstanceFrom($value)
    {
        if ($value === null) {
            return null;
        } elseif ($value instanceof DateTime) {
            return new DateValue($value->format('Y-m-d'));
        } else {
            return new DateValue($value);
        }
    }

    /**
     * Use input $value and return a valid DateTimeValue instance.
     *
     * @param  mixed              $value
     * @return DateTimeValue|null
     */
    protected function getDateTimeValueInstanceFrom($value)
    {
        if ($value === null) {
            return null;
        } elseif ($value instanceof DateTime) {
            return new DateTimeValue($value->format('Y-m-d H:i:s'), 'UTC');
        } else {
            return new DateTimeValue($value, 'UTC');
        }
    }

    // ---------------------------------------------------
    //  Database interaction
    // ---------------------------------------------------

    /**
     * Insert record in the database.
     */
    private function insert()
    {
        $last_insert_id = $this->connection->insert($this->table_name, $this->values);

        if (empty($this->values[$this->auto_increment])) {
            $this->values[$this->auto_increment] = $last_insert_id;
        }

        $this->values = array_merge($this->values, $this->refreshGeneratedFieldValues($last_insert_id));
        $this->setAsLoaded();
    }

    /**
     * Update database record.
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
                    throw new LogicException('Object #' . $this->getId() . " can't be overwritten");
                } else {
                    $this->connection->update($this->table_name, $updates, $this->getWherePartById($old_id));
                }
            } else {
                $this->connection->update($this->table_name, $updates, $this->getWherePartById($this->getId()));
            }

            $this->values = array_merge($this->values, $this->refreshGeneratedFieldValues($this->getId()));
            $this->setAsLoaded();
        }
    }

    /**
     * Return an array with potentially refreshed values of generated fields.
     *
     * @param  int   $id
     * @return array
     */
    private function refreshGeneratedFieldValues($id)
    {
        $result = [];

        if (!empty($this->generated_entity_fields)) {
            $result = $this->connection->selectFirstRow(
                $this->getTableName(),
                $this->generated_entity_fields,
                $this->getWherePartById($id)
            );

            if (empty($result)) {
                $result = [];
            }
        }

        $value_caster = $this->getGeneratedFieldsValueCaster();

        if ($value_caster instanceof ValueCasterInterface) {
            $value_caster->castRowValues($result);
        }

        return $result;
    }

    /**
     * Return where part of query.
     *
     * @param  int    $id
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
     * Reset modification idicators.
     *
     * Useful when you use setXXX functions but you dont want to modify
     * anything (just loading data from database in fresh object using
     * setFieldValue function)
     */
    private function resetModifiedFlags()
    {
        $this->modified_fields = [];
        $this->modified_attributes = [];
        $this->old_values = [];
        $this->primary_key_modified = false;
    }

    // ---------------------------------------------------
    //  Events
    // ---------------------------------------------------

    /**
     * Registered event handlers.
     *
     * @var array
     */
    private $event_handlers = [];

    /**
     * Register an internal event handler.
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
     * Trigger an internal event.
     *
     * @param string $event
     * @param array  $event_parameters
     */
    protected function triggerEvent($event, array $event_parameters = [])
    {
        if (isset($this->event_handlers[$event])) {
            foreach ($this->event_handlers[$event] as $handler) {
                call_user_func_array($handler, $event_parameters);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $result = [
            'id' => $this->getId(),
            'type' => get_class($this),
        ];

        $this->triggerEvent('on_json_serialize', [&$result]);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerializeDetails()
    {
        return [];
    }
}
