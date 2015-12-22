# DatabaseObject Library

[![Build Status](https://travis-ci.org/activecollab/databaseobject.svg?branch=master)](https://travis-ci.org/activecollab/databaseobject)

DatabaseObject library is a set of classes that make work with database just a bit easier. Here are the key concepts:

1. Types - entities that map to a single table,
2. Objects - type instances that map to a single row of a type table,
3. Collections - a group of objects that meets the given criteria. This group is perfect for HTTP responses because collections support data tagging and tag validation (ETag and HTTP 304),
4. Pool - manage registered types and make multi-type interaction possible,
6. Producers - customise the way pool produces new instances,
7. Validators - validate object properties before saving them to the database.

## CRUD

If you wish to work with entire tables, use CRUD methods provided by `ConnectionInterface`:

1. `ConnectionInterface::insert()` - insert one or more rows
2. `ConnectionInterface::update()` - update a set of rows that match the given conditions, if any
3. `ConnectionInterface::delete()` - drop a set of rows taht match the given conditions, if any

When you need to work with individual instances, `PoolInterface` provides following handy methods:

1. `PoolInterface::produce()` - create a new record based on the given parameters,
2. `PoolInterface::modify()` - change the given object with a set of parameters,
3. `PoolInterface::scrap()` - trash or permanently delete the given object.

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

Pool implements `ActiveCollab\ContainerAccessInterface`, so you can set any container that implements `Interop\Container\ContainerInterface` interface, and that container will be passed on and made available in finders, producers and objects:

```php
$container = new Container([
    'dependency' => 'it works!',
]);

$pool->setContainer($container);

foreach ($pool->find(Writer::class)->all() as $writer) {
    print $writer->dependency . "\n"; // Prints it works!
}
```

## To Do

1. Caching
2. Fix MySQL 5.6 installation on Travis CI
