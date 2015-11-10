<?php

namespace ActiveCollab\DatabaseObject\Test;

use ActiveCollab\DatabaseObject\Test\Base\WritersTypeTestCase;
use ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Collection as WritersCollection;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;

/**
 * Test data object collection
 *
 * @package angie.tests
 */
class TypeCollectionTest extends WritersTypeTestCase
{
    /**
     * Test if collection reads settings from type
     */
    function testTypeCollectionReadsSettingsFromType()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $this->assertEquals($collection->getTableName(), 'writers');
        $this->assertEquals($collection->getTimestampField(), 'updated_at');
        $this->assertEquals($collection->getOrderBy(), '`writers`.`id` DESC');
    }

    /**
     * Test set conditions from string
     */
    public function testSetConditionsFromString()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $collection->where('type = "File"');
        $this->assertEquals($collection->getConditions(), 'type = "File"');
    }

    /**
     * Test set conditions from array
     */
    public function testSetConditionsFromArray()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $collection->where(['type = ?', 'File']);
        $this->assertEquals($collection->getConditions(), "type = 'File'");
    }

    /**
     * Test set conditions from array of arguments
     */
    public function testSetConditionsFromArrayOfArguments()
    {
        $collection = new WritersCollection($this->connection, $this->pool);

        $collection->where('type = ?', 'File');
        $this->assertEquals($collection->getConditions(), "type = 'File'");
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyListOfArgumentsThrowsAnException()
    {
        (new WritersCollection($this->connection, $this->pool))->where();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConditionsTypeThrowsAnException()
    {
        (new WritersCollection($this->connection, $this->pool))->where(123);
    }

    /**
     * Test set order by
     */
    public function testSetOrderBy()
    {
        $collection = new WritersCollection($this->connection, $this->pool);
        $collection->orderBy('`writers`.`created_at` DESC');

        $this->assertEquals($collection->getOrderBy(), '`writers`.`created_at` DESC');
    }

    /**
     * Test if execute IDs returns a correct result
     */
    public function testExecuteIds()
    {
        $this->assertEquals([3, 2, 1], (new WritersCollection($this->connection, $this->pool))->executeIds());
    }

    /**
     * Test execute collection
     */
    public function testExecute()
    {
        $writers = (new WritersCollection($this->connection, $this->pool))->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(3, $writers);

        $this->assertEquals('Fyodor Dostoyevsky', $writers[0]->getName());
        $this->assertEquals('Alexander Pushkin', $writers[1]->getName());
        $this->assertEquals('Leo Tolstoy', $writers[2]->getName());
    }

    public function testExecuteWithConditions()
    {
        $writers = (new WritersCollection($this->connection, $this->pool))->where('`name` LIKE ? OR `name` LIKE ?', 'Fyodor%', 'Alexander%')->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(2, $writers);

        $this->assertEquals('Fyodor Dostoyevsky', $writers[0]->getName());
        $this->assertEquals('Alexander Pushkin', $writers[1]->getName());
    }

    public function testExecuteWithConditionsAndOrder()
    {
        $writers = (new WritersCollection($this->connection, $this->pool))->where('`name` LIKE ? OR `name` LIKE ?', 'Fyodor%', 'Alexander%')->orderBy('`name`')->execute();

        $this->assertInstanceOf(ResultInterface::class, $writers);
        $this->assertCount(2, $writers);

        $this->assertEquals('Alexander Pushkin', $writers[0]->getName());
        $this->assertEquals('Fyodor Dostoyevsky', $writers[1]->getName());
    }

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