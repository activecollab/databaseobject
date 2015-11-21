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

Finder can join a table, either by type:

```php
$this->pool->find(Writers::class)->join(WriterGroup::class)->where('`writer_groups`.`group_id` = ?', $group_id)->ids();
```

or directly by table name:

```php
$this->pool->find(Writers::class)->joinTable('writer_groups')->where('`writer_groups`.`group_id` = ?', $group_id)->ids();
```

## To Do

1. Caching
