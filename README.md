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

When pool works with an entire table, it uses following methods:

1. `PoolInterface::insert()` - insert one or more rows
2. `PoolInterface::update()` - update a set of rows that match the given conditions, if any
3. `PoolInterface::delete()` - drop a set of rows taht match the given conditions, if any

When pool works with an individual object instance, it uses following methods:

1. `PoolInterface::produce()` - create a new record based on the given parameters,
2. `PoolInterface::modify()` - change the given object with a set of parameters,
3. `PoolInterface::scrap()` - trash or permanently delete the given object.

## To Do

1. Caching
