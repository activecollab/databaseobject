# DatabaseObject Library

[![Build Status](https://travis-ci.org/activecollab/databaseobject.svg?branch=master)](https://travis-ci.org/activecollab/databaseobject)

DatabaseObject library is a set of classes that make work with database just a bit easier. Here are the key concepts:

1. Types - entities that map to a single table,
2. Objects - type instances that map to a single row of a type table,
3. Collections - a group of objects that meet the given criteria. This group is perfect for HTTP responses because collections support data tagging and tag validation,
4. Pool - manage registered types and make multi-type interaction possible,
6. Producers - customise the way pool produces new instances,
7. Validators - validate object properties before saving them to the database.

## To Do

1. Caching
