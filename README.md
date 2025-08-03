# DatabaseObject Library

[![Build Status](https://travis-ci.org/activecollab/databaseobject.svg?branch=master)](https://travis-ci.org/activecollab/databaseobject)

DatabaseObject library is a set of classes that make work with database just a bit easier. Here are the key concepts:

1. Types - entities that map to a single table,
1. Entities - type instances that map to a single row of a type table,
1. Entity Manager - instances that provide access to custom type specific manipulation methods,
1. Collections - a group of objects that meets the given criteria. This group is perfect for HTTP responses because collections support data tagging and tag validation (ETag and HTTP 304),
1. Pool - manage registered types and make multi-type interaction possible,
1. Producers - customise the way pool produces new instances,
1. Validators - validate object properties before saving them to the database.

## CRUD

If you wish to work with entire tables, use CRUD methods provided by `ConnectionInterface`:

1. `ConnectionInterface::insert()` - insert one or more rows
1. `ConnectionInterface::update()` - update a set of rows that match the given conditions, if any
1. `ConnectionInterface::delete()` - drop a set of rows taht match the given conditions, if any

When you need to work with individual instances, `PoolInterface` provides following handy methods:

1. `PoolInterface::produce()` - create a new record based on the given parameters,
1. `PoolInterface::modify()` - change the given object with a set of parameters,
1. `PoolInterface::scrap()` - trash or permanently delete the given object.

## Scrap

Recently we added `ScrapInterface`. This interface should be implemented by models which support object trashing, instead of instant deletion. When `PoolInterface::scrap()` method is called, objects that implement `ScrapInterface` will be scrapped (marked as deleted or trashed, depending on a particular implementation), instead of being permanently deleted.

## Finder

To set conditions, use `where` method:

```php
$pool->find(Writer::class)
     ->where('`birthday` > ?', '1800-01-01')
     ->ids();
```

This method can be called multiple times, and all conditions will be joined in one block with `AND` operator:

```php
$pool->find(Writer::class)
     ->where('`birthday` > ?', '1800-01-01')
     ->where('`birthday` < ?', '1825-01-01')
     ->ids();
```

Finder can join a table, either by table name:

```php
$pool->find(Writer::class)
     ->joinTable('writer_groups')
     ->where('`writer_groups`.`group_id` = ?', $group_id)
     ->ids();
```

or by related type:

```php
$pool->find(Writer::class)
     ->join(WriterGroup::class)
     ->where('`writer_groups`.`group_id` = ?', $group_id)
     ->ids();
```

Note that in the second case, `WriterGroup` type needs to be registered in the pool.

## DI Container

Pool implements `ActiveCollab\ContainerAccess\ContainerAccessInterface`, so you can set any container that implements `Interop\Container\ContainerInterface` interface, and that container will be passed on and made available in finders, producers and objects:

```php
$container = new Container([
    'dependency' => 'it works!',
]);

$pool->setContainer($container);

foreach ($pool->find(Writer::class)->all() as $writer) {
    print $writer->dependency . "\n"; // Prints it works!
}
```

## Generated Fields

Generated fields are fields that exist in tables, but they are not controlled or managed by the entity class itself. Instead, values of these models are set elsewhere:

1. They are specifief as generated columns in table's definition,
1. Trigger set the values,
1. Values are set by external systems or processes.

Library provides access to values of these fields, via accessors methods, but these values can't be set using setter methods:

```php
<?php

use ActiveCollab\DatabaseObject\Entity\Entity;

class StatsSnapshot extends Entity
{
    /**
     * Generated fields that are loaded, but not managed by the entity.
     *
     * @var array
     */
    protected $generated_fields = ['is_used_on_day', 'plan_name', 'number_of_users'];
    
    /**
     * Return value of is_used_on_day field.
     *
     * @return bool
     */
    public function isUsedOnDay()
    {
        return $this->getFieldValue('is_used_on_day');
    }

    /**
     * Return value of is_used_on_day field.
     *
     * @return bool
     * @deprecated use isUsedOnDay()
     */
    public function getIsUsedOnDay()
    {
        return $this->getFieldValue('is_used_on_day');
    }

    /**
     * Return value of plan_name field.
     *
     * @return string
     */
    public function getPlanName()
    {
        return $this->getFieldValue('plan_name');
    }

    /**
     * Return value of number_of_users field.
     *
     * @return int
     */
    public function getNumberOfUsers()
    {
        return $this->getFieldValue('number_of_users');
    }
}
```

Value casting can be set during entity configuration:

```php
<?php

use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DatabaseObject\Entity\Entity;

class StatsSnapshot extends Entity
{
    protected function configure(): void
    {
        $this->setGeneratedFieldsValueCaster(new ValueCaster([
            'is_used_on_day' => ValueCasterInterface::CAST_BOOL,
            'plan_name' => ValueCasterInterface::CAST_STRING,
            'number_of_users' => ValueCasterInterface::CAST_INT,
        ]));
    }
}
```

Entity class also refreshes the values of these fields on object save so fresh values are instantly available in case they are recalculated in the background (by a trigger or generated field expression).

## To Do

1. Caching,
2. Add support for PHP enums 
3. <del>Remove deprecated `ObjectInterface` and `Object` class.</del>
