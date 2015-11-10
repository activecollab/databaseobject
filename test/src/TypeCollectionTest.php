<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Collection as WritersCollection;

/**
 * Test data object collection
 *
 * @package angie.tests
 */
class TypeCollectionTest extends WritersTypeTestCase
{
    /**
     * Test is manager properly prepares collection
     */
    function testPrepare()
    {
//        $collection = TestDataObjects::prepareCollection('testing', $this->owner);

        $collection = new WritersCollection($this->connection, $this->pool);

        $this->assertEquals($collection->getTableName(), 'writers');
        $this->assertEquals($collection->getTimestampField(), 'updated_at');
        $this->assertEquals($collection->getOrderBy(), 'name');
    }

//    /**
//     * Test if we can properly set collection conditions
//     */
//    function testSetConditions()
//    {
//        $collection = TestDataObjects::prepareCollection('testing_all', $this->owner);
//
//        $collection->setConditions('type = "File"');
//        $this->assertEquals($collection->getConditions(), 'type = "File"');
//
//        $collection->setConditions('type = ?', 'File');
//        $this->assertEquals($collection->getConditions(), "type = 'File'");
//
//        $collection->setConditions(['type = ?', 'File']);
//        $this->assertEquals($collection->getConditions(), "type = 'File'");
//
//        try {
//            $collection->setConditions(123);
//            $this->fail('Invalid param exception');
//        } catch (InvalidParamError $e) {
//            $this->pass('InvalidParamError exception caught');
//        } // try
//    }
//
//    /**
//     * Test set order by
//     */
//    function testSetOrderBy()
//    {
//        $collection = TestDataObjects::prepareCollection('testing', $this->owner);
//
//        $this->assertEquals($collection->getOrderBy(), 'name');
//
//        $collection->setOrderBy('created_on DESC');
//
//        $this->assertEquals($collection->getOrderBy(), 'created_on DESC');
//    }
//
//    /**
//     * Test data fetching
//     */
//    function testFetching()
//    {
//        list($object1, $object2, $object3, $object4, $object5) = TestDataObjects::createMany([
//        ['type' => 'TestDataObject', 'name' => 'Object #1'],
//        ['type' => 'TestDataObject', 'name' => 'Object #2'],
//        ['type' => 'TestDataObject', 'name' => 'Object #3'],
//        ['type' => 'TestDataObject', 'name' => 'Object #4'],
//        ['type' => 'TestDataObject', 'name' => 'Object #5'],
//        ]);
//
//        $this->assertTrue($object1->isLoaded() && $object1->getId() === 1);
//        $this->assertTrue($object2->isLoaded() && $object2->getId() === 2);
//        $this->assertTrue($object3->isLoaded() && $object3->getId() === 3);
//        $this->assertTrue($object4->isLoaded() && $object4->getId() === 4);
//        $this->assertTrue($object5->isLoaded() && $object5->getId() === 5);
//
//        $collection = TestDataObjects::prepareCollection('testing_fetch', $this->owner);
//        $collection->setOrderBy('id');
//
//        $result = $collection->execute();
//
//        $this->assertIsA($result, 'DBResult');
//        $this->assertEquals($result->count(), 5);
//        $this->assertEquals($result->getRowAt(0)->getId(), 1);
//        $this->assertEquals($result->getRowAt(1)->getId(), 2);
//        $this->assertEquals($result->getRowAt(2)->getId(), 3);
//        $this->assertEquals($result->getRowAt(3)->getId(), 4);
//        $this->assertEquals($result->getRowAt(4)->getId(), 5);
//    }
//
//    /**
//     * Test collection join
//     */
//    function testJoin()
//    {
//        DB::execute("CREATE TABLE test_data_object_join_table (
//        test_data_object_id int(10) unsigned NOT NULL,
//        user_id int(10) unsigned NOT NULL,
//        PRIMARY KEY  (test_data_object_id, user_id)
//      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
//
//        $this->assertTrue(DB::tableExists('test_data_object_join_table'));
//
//        list($object1, $object2, $object3) = TestDataObjects::createMany([
//        ['type' => 'TestDataObject', 'name' => 'Object #1'],
//        ['type' => 'TestDataObject', 'name' => 'Object #2'],
//        ['type' => 'TestDataObject', 'name' => 'Object #3'],
//        ]);
//
//        if ($object1 instanceof TestDataObject || $object2 instanceof TestDataObject && $object3 instanceof TestDataObject) {
//            $this->assertTrue($object1->isLoaded() && $object1->getId() === 1);
//            $this->assertTrue($object2->isLoaded() && $object2->getId() === 2);
//            $this->assertTrue($object3->isLoaded() && $object3->getId() === 3);
//        } else {
//            $this->fail('Failed to create object instances');
//        }
//
//        DB::execute("INSERT INTO test_data_object_join_table (test_data_object_id, user_id) VALUES (1, 1), (2, 2), (3, 1)");
//
//        $this->assertEquals((integer)DB::executeFirstCell("SELECT COUNT(*) FROM test_data_object_join_table"), 3);
//
//        $collection = TestDataObjects::prepareCollection('testing_join', $this->owner);
//        $collection->setJoinTable('test_data_object_join_table');
//
//        $this->assertEquals($collection->getJoinTable(), 'test_data_object_join_table');
//        $this->assertEquals($collection->getJoinField(), 'id');
//        $this->assertEquals($collection->getJoinWithField(), 'test_data_object_id');
//
//        $this->assertEquals($collection->count(), 3);
//
//        $collection->setConditions("test_data_object_join_table.user_id = ?", 1);
//
//        $this->assertEquals($collection->count(), 2);
//
//        $result = $collection->execute();
//
//        if ($result instanceof DBResult) {
//            $this->assertEquals($result->count(), 2);
//            $this->assertEquals($result->getRowAt(0)->getId(), 1);
//            $this->assertEquals($result->getRowAt(1)->getId(), 3);
//        } else {
//            $this->fail('Invalid collection execution result. DBResult instance expected');
//        }
//    }
//
//    /**
//     * Test etag signing
//     */
//    function testEtagSigning()
//    {
//        $logged_user = new Owner(1);
//
//        $this->assertTrue($logged_user->isLoaded());
//
//        $yesterday = DateTimeValue::makeFromString('-1 day');
//        $yesterday_string = $yesterday->toMySQL();
//        $today_string = DateTimeValue::now()->toMySQL();
//
//        $test_object_1 = new TestDataObject();
//        $test_object_1->setName('Test object #1');
//        $test_object_1->setCreatedOn($yesterday);
//        $test_object_1->setUpdatedOn($yesterday);
//        $test_object_1->save();
//
//        $this->assertTrue($test_object_1->isLoaded());
//        $this->assertEquals($test_object_1->getId(), 1);
//        $this->assertEquals($test_object_1->getCreatedOn()->toMySQL(), $yesterday_string);
//        $this->assertEquals($test_object_1->getUpdatedOn()->toMySQL(), $yesterday_string);
//
//        $test_object_2 = new TestDataObject();
//        $test_object_2->setName('Test object #2');
//        $test_object_2->setCreatedOn($yesterday);
//        $test_object_2->setUpdatedOn($yesterday);
//        $test_object_2->save();
//
//        $this->assertTrue($test_object_2->isLoaded());
//        $this->assertEquals($test_object_2->getId(), 2);
//        $this->assertEquals($test_object_2->getCreatedOn()->toMySQL(), $yesterday_string);
//        $this->assertEquals($test_object_2->getUpdatedOn()->toMySQL(), $yesterday_string);
//
//        $collection = TestDataObjects::prepareCollection('testing_etag', $this->owner);
//        $this->assertIsA($collection, 'DataObjectCollection');
//
//        $etag = $collection->getTag($logged_user);
//
//        $this->assertEquals($etag, '"current,collection,TestDataObjects,testing_etag,' . $logged_user->getEmail() . ',' . sha1("$yesterday_string,$yesterday_string") . '"');
//
//        $test_object_2->setName('Updated name');
//        $test_object_2->save();
//
//        // Tag changed in the background, so we have an invalid value cached
//        $this->assertNotEquals($collection->getTag($logged_user), 'current,collection,TestDataObjects,testing_etag,' . $logged_user->getEmail() . ',' . sha1("$yesterday_string,$today_string"));
//
//        // On refresh, we have a valid value ($use_cache false for getTag() method)
//        $this->assertEquals($collection->getTag($logged_user, false), '"current,collection,TestDataObjects,testing_etag,' . $logged_user->getEmail() . ',' . sha1("$yesterday_string,$today_string") . '"');
//
//        $this->assertFalse($collection->validateTag('"current,collection,TestDataObjects,testing_etag,' . $logged_user->getEmail() . ',' . sha1("$yesterday_string,$yesterday_string") . '"', $logged_user, false));
//        $this->assertTrue($collection->validateTag('"current,collection,TestDataObjects,testing_etag,' . $logged_user->getEmail() . ',' . sha1("$yesterday_string,$today_string") . '"', $logged_user, false));
//    }
}