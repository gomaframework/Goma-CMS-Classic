<?php
namespace Goma\Test\Model;
use ArrayList;
use Closure;
use Controller;
use DataObject;
use DataObjectSet;
use DataSet;
use Exception;
use GomaUnitTest;
use HasMany_DataObjectSet;
use IDataObjectSetDataSource;
use IDataObjectSetModelSource;
use ManyMany_DataObjectSet;
use MySQLException;
use ReflectionMethod;
use User;
use ViewAccessableData;

defined("IN_GOMA") OR die();

/**
 * Unit-Tests for ManyManyRelationShipInfo-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DataObjectSetTests extends GomaUnitTest
{

    static $area = "NModel";
    /**
     * name
     */
    public $name = DataObjectSet::class;

    /**
     * @var DumpDBElementPerson
     */
    protected $daniel;

    /**
     * @var DumpDBElementPerson
     */
    protected $kathi;

    /**
     * @var DumpDBElementPerson
     */
    protected $patrick;

    /**
     * @var DumpDBElementPerson
     */
    protected $janine;

    /**
     * @var DumpDBElementPerson
     */
    protected $nik;

    /**
     * @var DumpDBElementPerson
     */
    protected $julian;

    /**
     * @var DumpDBElementPerson
     */
    protected $fabian;

    /**
     * @var DumpDBElementPerson
     */
    protected $franz;

    /**
     * @var DumpDBElementPerson
     */
    protected $lisa;

    /**
     * @var DumpDBElementPerson
     */
    protected $julia;

    /**
     * @var DumpDBElementPerson
     */
    protected $jenny;

    /**
     * @var DumpDBElementPerson[]
     */
    protected $allPersons;

    public function setUp()
    {
        $this->daniel =  new DumpDBElementPerson("Daniel", 20, "M");
        $this->kathi = new DumpDBElementPerson("Kathi", 22, "W");
        $this->patrick = new DumpDBElementPerson("Patrick", 16, "M");
        $this->janine = new DumpDBElementPerson("Janine", 19, "W");
        $this->nik = new DumpDBElementPerson("Nik", 21, "M");
        $this->julian = new DumpDBElementPerson("Julian", 20, "M");
        $this->fabian = new DumpDBElementPerson("Fabian", 22, "M");
        $this->franz = new DumpDBElementPerson("Franz", 56, "M");
        $this->lisa = new DumpDBElementPerson("Lisa", 18, "W");
        $this->julia = new DumpDBElementPerson("Julia", 25, "W");
        $this->jenny = new DumpDBElementPerson("Jenny", 35, "W");

        $this->allPersons = array($this->daniel, $this->kathi, $this->patrick, $this->nik,
                                  $this->julian, $this->janine, $this->fabian, $this->franz,
                                  $this->lisa, $this->julia, $this->jenny);


        foreach($this->allPersons as $person) {
            $person->queryVersion = DataObject::VERSION_PUBLISHED;
        }
    }

    /**
     * relationship env.
     */
    public function testCount() {
        $data = DataObject::get(User::class);
        $count = $data->count();

        $data->add(new User());
        $this->assertEqual($data->count(), $count + 1);
    }

    public function testDataClass() {
        $this->unittestDataClass("123");
        $this->unittestDataClass("blub");
        $this->unittestDataClass(null);
    }

    public function testAssignFields() {
        $this->unittestAssignFields(MockDataObjectForDataObjectSet::class);
        $this->unittestAssignFields(new MockDataObjectForDataObjectSet());
        $this->unittestAssignFields(new MockIDataObjectSetDataSource(User::class));

        $mockInExp = new MockDataObjectForDataObjectSet();
        $mockInExp->inExpansion = "blah";
        $this->unittestAssignFields($mockInExp, "blah");
        $set = new DataObjectSet();
        $set->setDbDataSource(new MockIDataObjectSetDataSource(User::class, "tja"));
        $this->assertEqual($set->inExpansion, "tja");

        $this->unittestAssignFields(new MockIDataObjectSetDataSource(User::class, "blub"), "blub");

        $set = $this->unittestAssignFields(array($source = new MockIDataObjectSetDataSource(), $model = new MockIModelSource()));

        $this->assertEqual($set->getDbDataSource(), $source);
        $this->assertEqual($set->getModelSource(), $model);

        $set = $this->unittestAssignFields(DumpDBElementPerson::class);
        $this->assertIsA($set->getDbDataSource(), MockIDataObjectSetDataSource::class);
        $this->assertIsA($set->getModelSource(), MockIModelSource::class);

        $emptySet = new DataObjectSet($modelSource = new MockIModelSource(DataObject::class));
        $this->assertNull($emptySet->getDbDataSource());
        $this->assertEqual($emptySet->getModelSource(), $modelSource);
    }

    public function unittestAssignFields($object, $inExpansion = null) {
        $set = new DataObjectSet($object);
        $this->assertIsA($set->getModelSource(), IDataObjectSetModelSource::class);
        $this->assertIsA($set->getDbDataSource(), IdataObjectSetDataSource::class);
        $this->assertEqual($inExpansion, $set->inExpansion);

        return $set;
    }

    public function unittestDataClass($class) {
        $mockDataSource = new MockIDataObjectSetDataSource();
        $mockModelSource = new MockIModelSource();

        $mockModelSource->_dataClass = $class;
        $mockDataSource->_dataClass = $class;

        $set = new DataObjectSet($mockDataSource);
        $this->assertEqual($set->DataClass(), $class);

        $set->setModelSource($mockModelSource);
        $this->assertEqual($set->DataClass(), $class);

        $secondSet = new DataObjectSet();
        $secondSet->setModelSource($mockModelSource);
        $this->assertEqual($secondSet->DataClass(), $class);

        $mockModelSource->_dataClass = $class . "_";
        $this->assertEqual($secondSet->DataClass(), $class . "_");
        $this->assertEqual($set->DataClass(), $class);
    }

    public function setDataTest() {
        $object = new HasMany_DataObjectSet(User::class);
        $object->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);
        $this->assertEqual($object->count(), 0);
        $this->assertEqual($object->first(), null);
        $this->assertEqual($object->last(), null);
    }

    public function testDataObject() {
        $object = new HasMany_DataObjectSet();
        $this->assertThrows(function() use($object) {
            $object->first();
        }, "InvalidArgumentException");

        $object = new HasMany_DataObjectSet(User::class);
        $object->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);
        $this->assertNull($object->first());
    }

    public function testcreateFromCode()
    {
        $set = new DataObjectSet(User::class);
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);
        $set->add($user1 = new User());
        $set->add($user2 = new User());

        $this->assertEqual($set->count(), 2);
    }

    public function testcreateFromCodeDuplicate()
    {
        $set = new DataObjectSet(User::class);
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);
        $set->add($user1 = new User());
        $set->add($user2 = new User());

        $this->assertThrows(function() use ($set, $user1) {
            $set->add($user1);
        }, "LogicException");

        $this->assertEqual($set->count(), 2);
    }

    /**
     * tests if user which was created at build is searchable.
     * @throws MySQLException
     */
    public function testSearchCreatedTestUserAtBuild() {
        $data = DataObject::get(User::class);
        $clone = clone $data;

        $count = $data->count();

        $data->search("admin");
        $this->assertIsA($data->first(), DataObject::class);
        $this->assertEqual($clone->count(), $count);
    }

    /**
     * tests if user which was created at build is searchable.
     * @throws MySQLException
     */
    public function testSearchCreatedUserAtRuntime() {
        try {
            $user = new User(array(
                "nickname" => "______test",
                "password" => "test"
            ));
            $user->writeToDB(true, true);
            $data = DataObject::get(User::class);
            $clone = clone $data;

            $count = $data->count();
            $first = $data->first();

            $data->search("______test");
            $this->assertNotEqual($count, $data->count());
            $this->assertIsA($data->first(), DataObject::class);
            $this->assertEqual($clone->count(), $count);

            $this->assertEqual($clone->first(), $first);
        } finally {
            $user->remove(true);
        }
    }

    public function testFirstLast() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();
        $source->records = array(
            $this->janine,
            $this->daniel
        );

        $this->assertEqual($set->first(), $this->janine);
        $this->assertEqual($set->last(), $this->daniel);
        $this->assertEqual($set->forceData()->count(), 2);

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine
        );

        $this->assertEqual($set->first(), $this->janine);
        $this->assertEqual($set->last(), $this->daniel);
        $set->filter(array("name" => "blah"));
        $this->assertEqual($set->first(), null);
        $this->assertEqual($set->last(), null);

        $set->filter(array());

        $this->assertEqual($set->first(), $this->julian);
        $this->assertEqual($set->last(), $this->janine);

        $this->assertEqual($set[1], $this->daniel);
        $this->assertEqual($set[2], $this->janine);
        $this->assertEqual($set[3], null);
    }

    /**
     *
     */
    public function testFirstLastWithPersistence() {

    }

    public function testPagination() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi,
            $this->patrick,
            $this->nik
        );

        $set->activatePagination(null, 2);
        $this->assertEqual($set->count(), 2);
        $this->assertEqual($set->first(), $this->julian);
        $this->assertEqual($set->last(), $this->daniel);

        $i = 0;
        foreach($set as $record) {
            $this->assertEqual($source->records[$i]->ToArray(), $record->ToArray());
            $i++;
        }

        $this->assertEqual($i, 2);

        $set->activatePagination(2);
        $this->assertEqual($set->getPage(), 2);

        $this->assertEqual($set->first(), $this->janine);
        $this->assertEqual($set->last(), $this->kathi);

        $set->activatePagination(3);
        $this->assertEqual($set->getPage(), 3);

        $this->assertEqual($set->first(), $this->patrick);
        $this->assertEqual($set->last(), $this->nik);
        $this->assertEqual($set[1], $this->nik);

        $set->disablePagination();

        $this->assertEqual($set[4], $this->patrick);
    }

    public function testEmptyPagination() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array();

        $set->activatePagination(null, 2);

        $this->assertEqual($set->count(), 0);
        $this->assertEqual($set->getPageCount(), 0);
    }

    public function testStaging() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $this->assertEqual($set->count(), 4);
        $set->add($this->patrick);

        $this->assertEqual($set->count(), 5);
        $this->assertEqual($set[4], $this->patrick);
        $this->assertEqual($set->last(), $this->patrick);

        try {
            $set->commitStaging();
        } catch(Exception $e) {
            $this->assertIsA($e, "DataObjectSetCommitException");
            $this->assertEqual($set->getStaging()->find("name", "patrick", true), $this->patrick);
        }

        $set->removeFromStage($this->patrick);

        $this->assertEqual($set[4], null);
        $this->assertEqual($set->last(), $this->kathi);
    }

    public function testStagingMulti() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $this->assertEqual($set->count(), 4);
        $set->add($this->patrick);
        $set->add($this->nik);

        $this->assertEqual($set->count(), 6);
        $this->assertEqual($set[4], $this->patrick);
        $this->assertEqual($set->last(), $this->nik);
        $this->assertEqual($set[3], $this->kathi);

        $set->removeFromStage($this->patrick);
        $this->assertEqual($set[4], $this->nik);
        $this->assertEqual($set->count(), 5);
    }

    public function testCustomised() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $set->customise(array(
            "blub" => 123
        ));

        foreach($set as $record) {
            $this->assertEqual($record->blub, 123);
        }

        $this->assertEqual($set->blub, 123);
        $this->assertEqual($set[3]->blub, 123);

        $objectWithoutCustomisation = $set->getObjectWithoutCustomisation();
        $this->assertEqual($objectWithoutCustomisation[3]->blub, null);
    }

    public function testRanges() {
        $set = new DataObjectSet(DumpDBElementPerson::class);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $this->assertFalse($set->isDataLoaded());

        $this->assertIsA($set->getRange(0, 1), DataSet::class);
        $this->assertIsA($set->getRange(0, 1)->ToArray(), "array");
        $this->assertIsA($set->getArrayRange(0, 1), "array");

        $this->assertFalse($set->isDataLoaded());

        $this->assertIsA($set->ToArray(), "array");
        $this->assertEqual($source->records, $set->ToArray());

        $this->assertTrue($set->isDataLoaded());

        $this->assertEqual(array($this->julian), $set->getArrayRange(0, 1));

        $set->add($this->patrick);
        $this->assertTrue($set->isDataLoaded());

        $this->assertTrue($set->isInStage($this->patrick));
        $this->assertFalse($set->isInStage($this->daniel));

        $merged = array_merge($source->records, array($this->patrick));
        $this->assertIsA($merged, "array");

        $this->assertEqual($merged, $set->ToArray());
        $this->assertEqual($merged, $set->getArrayRange(0, 5));

        $set->removeFromStage($this->patrick);
        $this->assertEqual($source->records, $set->ToArray());
        $this->assertEqual($source->records, $set->getArrayRange(0, 5));
    }

    public function testRangesNew() {
        $set = new DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $this->assertIsA($set->getRange(0, 1), DataSet::class);
        $this->assertIsA($set->getRange(0, 1)->ToArray(), "array");
        $this->assertIsA($set->getArrayRange(0, 1), "array");

        $set->add($this->janine);

        $this->assertIsA($set->getRange(0, 1), DataSet::class);
        $this->assertIsA($set->getRange(0, 1)->ToArray(), "array");
        $this->assertIsA($set->getArrayRange(0, 1), "array");
    }

    public function testObjectPersistence() {
        $this->unittestObjectPersistence(array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        ));
        $this->unittestObjectPersistence(array(
            array("name" => "janine"),
            array("name" => "daniel"),
            array("name" => "julian"),
            array("name" => "kathi"),
        ));
    }

    public function unittestObjectPersistence($records) {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = $records;

        $cacheMethod = new ReflectionMethod(DataObjectSet::class, "clearCache");
        $cacheMethod->setAccessible(true);

        $this->assertTrue($set[0] === $set->first());
        $this->assertTrue($set[count($records) - 1] === $set->last());

        $this->assertTrue($set->first() === $set[0]);
        $this->assertTrue($set->last() === $set[count($records) - 1]);

        $i = 0;
        foreach($set as $record) {
            if($i == 0) {
                $this->assertTrue($record === $set->first());
            } else {
                $this->assertFalse($record === $set->first());
            }

            if($i == count($records) - 1) {
                $this->assertTrue($record === $set->last());
            } else {
                $this->assertFalse($record === $set->last());
            }
            $i++;
        }

        $set->activatePagination(1, 2);

        if(is_array($records[1])) {
            $this->assertEqual($set[1]->ToArray(), $records[1]);
            $this->assertEqual($set->last()->ToArray(), $records[1]);
        } else {
            $this->assertEqual($set[1], $records[1]);
            $this->assertEqual($set->last(), $records[1]);
        }
        $i = 0;
        foreach($set as $record) {
            if($i == 0) {
                $this->assertIdentical($record, $set->first());
            } else {
                $this->assertFalse($record === $set->first());
            }

            if($i == 1) {
                $this->assertIdentical($record, $set->last());
            } else {
                $this->assertFalse($record === $set->last());
            }
            $i++;
        }

        $cacheMethod->invoke($set);
        $i = 0;
        foreach($set as $record) {
            if($i == 0) {
                $this->assertIdentical($record, $set->first());
            } else {
                $this->assertFalse($record === $set->first());
            }

            if($i == 1) {
                $this->assertIdentical($record, $set->last());
            } else {
                $this->assertFalse($record === $set->last());
            }
            $i++;
        }
    }

    public function findTest() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $this->assertEqual($set->find("name", "julian"), $this->julian);
        $set->forceData();
        $this->assertEqual($set->find("name", "julian"), $this->julian);

        $this->assertEqual($set->find("age", "19"), $this->janine);
        $this->assertEqual($set->find("name", "JULIAN", false), null);
        $this->assertEqual($set->find("name", "JULIAN", true), $this->julian);
    }

    public function findTestNew() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);
        $this->assertEqual($set->ToArray(), array());

        $this->assertEqual($set->find("name", "julian"), null);
        $set->forceData();
        $this->assertEqual($set->find("name", "julian"), null);

        $set->add($this->julian);
        $this->assertEqual($set->find("name", "julian"), $this->julian);
        $set->forceData();
        $this->assertEqual($set->find("name", "julian"), $this->julian);
        $set->add($this->janine);

        $this->assertEqual($set->find("age", "19"), $this->janine);
        $this->assertEqual($set->find("name", "JULIAN", false), null);
        $this->assertEqual($set->find("name", "JULIAN", true), $this->julian);
    }

    public function testAdd() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->commitStaging();
        $this->assertEqual($set->getFetchMode(), DataObjectSet::FETCH_MODE_EDIT);

        $this->assertEqual(0, $set->getStaging()->count());
        $set->getStaging()->add($this->janine);
        $this->assertEqual(1, $set->getStaging()->count());

        $set->getStaging()->remove($this->janine);
        $this->assertEqual(0, $set->getStaging()->count());

        $set->add($this->janine);
        $this->assertEqual(1, $set->getStaging()->count());
    }

    public function testCommitStaging() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->commitStaging();
        $this->assertEqual($set->getFetchMode(), DataObjectSet::FETCH_MODE_EDIT);

        $this->assertEqual(0, $set->getStaging()->count());
        $set->add($this->janine);
        $this->assertEqual(1, $set->getStaging()->count());
        try {
            $set->commitStaging();
            $this->assertEqual(true, false);
        } catch(Exception $e) {
            $this->assertEqual($e->getMessage(), "1 record(s) of type ".DumpDBElementPerson::class." could not be written.");
        }
    }

    public function testStagingFilter() {
        $set = new DataObjectSet(DumpDBElementPerson::class);

        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $this->assertEqual($set->count(), 4);

        $set->filter(array("blah = 'blub'"));
        $this->assertEqual($set->count(), 0);
        $this->assertEqual($set->first(), null);

        $set->add($this->patrick);

        $this->assertEqual($set->count(), 0);
        $this->assertEqual($set->first(), null);

        $set->filter();

        $this->assertEqual($set->count(), 5);
        $set->add($this->nik);
        $this->assertEqual($set->count(), 6);
        $this->assertEqual($set->last(), $this->nik);

        $set->filter("name", "Nik");
        $this->assertEqual($set->count(), 1);

        $set->addFilter(array("true"));
        $this->assertEqual($set->count(), 0);
    }

    public function testCreateNew() {
        $set = new DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->add($this->nik);
        $set->add($this->janine);
        $set->add($this->patrick);

        $this->assertEqual(3, $set->count());
        $this->assertEqual(3, count($set->ToArray()));
        $this->assertEqual(3, $set->getStaging()->count());
        $this->assertEqual(3, count($set->getStaging()->ToArray()));

        $this->assertEqual($this->nik, $set[0]);
        $this->assertEqual($this->janine, $set[1]);
        $this->assertEqual($this->patrick, $set[2]);

        $this->assertEqual($this->nik, $set->first());
        $this->assertEqual($this->patrick, $set->last());

        $set->add($this->kathi);

        $this->assertEqual($this->kathi, $set->last());
        $this->assertEqual($this->patrick, $set[2]);
    }

    public function testCreateNewBig() {
        $set = new DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $this->assertTrue(count($this->allPersons) > 10);

        foreach($this->allPersons as $person) {
            $set->add($person);
        }

        $this->assertEqual(count($this->allPersons), $set->count());
        $this->assertEqual(count($this->allPersons), count($set->ToArray()));

        $i = 0;
        foreach($set as $record) {
            $i++;
        }
        $this->assertEqual(count($this->allPersons), $i);
    }

    /**
     * tests if loop with one only have one element.
     */
    public function testLoopOneElement() {
        $set = new DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->add($this->patrick);

        $i = 0;
        foreach($set as $item) {
            $this->assertEqual($this->patrick->name, $item->name);
            $i++;
        }
        $this->assertEqual(1, $i);
    }

    /**
     * tests if loop with one only have one element if first has been called.
     */
    public function testLoopOneElementAfterFirst() {
        $set = new DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->add($this->patrick);

        $this->assertEqual($this->patrick, $set->first());
        $i = 0;
        foreach($set as $item) {
            $this->assertEqual($this->patrick->name, $item->name);
            $i++;
        }
        $this->assertEqual(1, $i);
    }

    /**
     * tests if loop with one only have one element if first has been called.
     */
    public function testLoopOneElementAfterFirstEdit() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->add($this->patrick);

        $this->assertEqual($this->patrick, $set->first());
        $i = 0;
        foreach($set as $item) {
            $this->assertEqual($this->patrick->name, $item->name);
            $i++;
        }
        $this->assertEqual(1, $i);
    }

    /**
     * tests if loop with n elements have n elements if first has been called.
     * Edit-Mode!
     */
    public function testLoopMultiElementAfterFirstEdit() {
        $set = new DataObjectSet(DumpDBElementPerson::class);

        $a = 5;
        for($i = 0; $i < $a; $i++) {
            $set->add($this->allPersons[$i]);
        }

        $this->assertEqual($this->allPersons[0], $set->first());
        $i = 0;
        foreach($set as $item) {
            $this->assertEqual($this->allPersons[$i]->name, $item->name, "Element $i is problematic.");
            $i++;
        }
        $this->assertEqual($a, $i);
    }

    public function testRemoveInLoop() {
        $set = new DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $this->assertTrue(count($this->allPersons) > 10);

        foreach($this->allPersons as $person) {
            $set->add($person);
        }

        $this->assertEqual(count($this->allPersons), $set->count());
        $this->assertEqual(count($this->allPersons), count($set->ToArray()));

        $i = 0;
        foreach($set as $record) {
            $this->assertTrue($set->isInStage($record));
            $this->assertEqual($this->allPersons[$i], $record);
            if($i == 3) {
                $set->removeFromStage($record);
                $this->assertFalse($set->isInStage($record));
            }
            $i++;
        }
        $this->assertEqual(count($this->allPersons), $i);
        $this->assertEqual(count($this->allPersons) - 1, $set->count());
        $this->assertEqual(count($this->allPersons) - 1, count($set->ToArray()));
    }

    /**
     * @testdox tests if filter through new created set works
     */
    public function testfilterTroughNew() {
        $set = new DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->add($this->janine);
        $set->add($this->daniel);
        $set->add($this->patrick);

        $this->assertEqual(3, $set->count());
        $this->assertEqual($this->janine, $set->first());
        $this->assertEqual($this->patrick, $set->last());

        $set->addFilter(array(
            "gender" => "M"
        ));
        $this->assertEqual(2, $set->count());
    }

    /**
     * @testdox tests if addFilter is appending to filter with AND conjunction.
     */
    public function testAddFilterIsAppendingWithAND() {
        $set = new DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->add($this->janine);
        $set->add($this->daniel);
        $set->add($this->patrick);

        $this->assertEqual(3, $set->count());
        $this->assertEqual($this->janine, $set->first());
        $this->assertEqual($this->patrick, $set->last());

        $set->addFilter(array(
            "age" => "19"
        ));
        $this->assertEqual(1, $set->count());
        $set->addFilter(array(
            "gender" => "W", "OR", "age" => 11
        ));
        $this->assertEqual(0, $set->count());
    }

    /**
     * @testdox tests if addFilter is appending to filter with OR conjunction.
     */
    public function testAddOrCondition() {
        $set = new DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->add($this->janine);
        $set->add($this->daniel);
        $set->add($this->patrick);

        $this->assertEqual(3, $set->count());
        $this->assertEqual($this->janine, $set->first());
        $this->assertEqual($this->patrick, $set->last());

        $set->addFilter(array(
            "age" => 20
        ));
        $this->assertEqual(1, $set->count());
        $set->addORCondition(array(
            "age" => 19
        ));
        $this->assertEqual(2, $set->count());
    }

    /**
     * @testdox tests if sorted pagination with mixed data works
     */
    public function testSortWithMixedDataObjectSet() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $this->assertEqual(4, $set->count());

        $set->activatePagination(1, 4);
        $this->assertEqual(4, $set->count());

        $set->add($this->patrick);
        $set->add($this->lisa);

        $this->assertEqual(4, $set->count());
        $set->sort("age", "asc");

        $this->assertEqual($this->patrick, $set->first());

        $set->activatePagination(1, 6);
        $start = $set->first()->age;
        $this->assertEqual($this->patrick->age, $start);
        foreach($set as $item) {
            $this->assertTrue($item->age >= $start);
            $start = $item->age;
        }
    }

    /**
     * @test tests if sorted pagination with mixed data works
     */
    public function testSortAndFilterWithMixedDataObjectSet() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $this->assertEqual(4, $set->count());

        $set->activatePagination(1, 4);
        $this->assertEqual(4, $set->count());

        $set->add($this->patrick);
        $set->add($this->lisa);
        $set->add($this->franz);

        $this->assertEqual(4, $set->count());
        $set->filter(array("gender" => "M"));
        $set->sort("age", "asc");

        $this->assertEqual(4, $set->count());
        $this->assertEqual(4, $set->countWholeSet());

        $this->assertEqual($this->patrick, $set->first());
        $this->assertEqual($this->franz, $set->last());
    }

    /**
     * @test tests basic sort
     */
    public function testSort() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $set->sort("name", "DESC");
        $this->assertEqual($this->kathi, $set->first());
        $this->assertEqual($this->daniel, $set->last());

        $set->sort("age", "ASC");
        $this->assertEqual($this->janine, $set->first());
        $this->assertEqual($this->kathi, $set->last());
    }

    public function testResetSort() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $set->sort("name", "DESC");
        $this->assertEqual($this->kathi, $set->first());
        $this->assertEqual($this->daniel, $set->last());
        $this->assertEqual($this->julian, $set[1]);

        $this->assertTrue($set->isDataLoaded());

        $set->sort();
        $this->assertFalse($set->isDataLoaded());

        $this->assertEqual($this->julian, $set->first());
        $this->assertFalse($set->isDataLoaded());

        $this->assertEqual($this->daniel, $set[1]);
        $this->assertTrue($set->isDataLoaded());
    }

    public function testSortWithArray() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $set->sort(array("name" => "DESC"));
        $this->assertEqual($this->kathi, $set->first());
        $this->assertEqual($this->daniel, $set->last());

        $set->sort(array("age" => "ASC"));
        $this->assertEqual($this->janine, $set->first());
        $this->assertEqual($this->kathi, $set->last());

        $secondSet = new DataObjectSet(DumpDBElementPerson::class);
        $secondSet->setVersion(DataObject::VERSION_PUBLISHED);
        $secondSet->sort("age");

        $secondSource = $secondSet->getDbDataSource();
        $secondSource->records = $source->records;

        $this->assertEqual($this->janine, $secondSet->first());
        $this->assertEqual($this->kathi, $secondSet->last());
    }

    public function testMultiSortWithArray() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $set->sort(array("age" => "ASC", "name" => "DESC"));
        $this->assertEqual($this->janine, $set->first());
        $this->assertEqual($this->julian, $set[1]);
        $this->assertEqual($this->daniel, $set[2]);
        $this->assertEqual($this->kathi, $set->last());

        $set->sort(array("age" => "ASC", "name" => "ASC"));
        $this->assertEqual($this->janine, $set->first());
        $this->assertEqual($this->julian, $set[2]);
        $this->assertEqual($this->daniel, $set[1]);
        $this->assertEqual($this->kathi, $set->last());
    }

    /**
     * tests grouping.
     */
    public function testGroupBy() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $source->group = array(
            array(
                $this->julian,
                $this->daniel,
            ),
            array(
                $this->janine,
                $this->kathi
            )
        );

        $this->assertEqual($set->ToArray(), $source->records);
        $set->groupBy("blub");
        $this->assertEqual($set->ToArray(), $source->group);
    }

    /**
     * tests reset at grouping.
     */
    public function testGroupByReset() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->julian,
            $this->daniel,
            $this->janine,
            $this->kathi
        );

        $source->group = array(
            array(
                $this->julian,
                $this->daniel,
            ),
            array(
                $this->janine,
                $this->kathi
            )
        );

        $set->groupBy("blub");
        $this->assertEqual($set->ToArray(), $source->group);
        $set->groupBy(null);
        $this->assertEqual($set->ToArray(), $source->records);
    }

    /**
     * tests if getForm is called on the model.
     */
    public function testGetFormOnModel() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setModelSource($source = new MockIModelSource());

        $source->model = new MockFormModel();

        $this->assertEqual($source->model->getFormCalled, 0);
        $this->assertEqual($source->model->getEditFormCalled, 0);
        $this->assertEqual($source->model->getActionsCalled, 0);

        $set->generateForm(null, false, false, null, new Controller());

        $this->assertEqual($source->model->getFormCalled, 1);
        $this->assertEqual($source->model->getEditFormCalled, 0);
        $this->assertEqual($source->model->getActionsCalled, 1);
    }

    /**
     * tests if getEditForm is called on the model.
     */
    public function testGetEditFormOnModel() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setModelSource($source = new MockIModelSource());

        $source->model = new MockFormModel();

        $this->assertEqual($source->model->getFormCalled, 0);
        $this->assertEqual($source->model->getEditFormCalled, 0);
        $this->assertEqual($source->model->getActionsCalled, 0);

        $set->generateForm(null, true, false, null, new Controller());

        $this->assertEqual($source->model->getFormCalled, 0);
        $this->assertEqual($source->model->getEditFormCalled, 1);
        $this->assertEqual($source->model->getActionsCalled, 1);
    }
}

class MockFormModel extends ViewAccessableData {

    public $getFormCalled = 0;
    public $getEditFormCalled = 0;
    public $getActionsCalled = 0;

    public function getForm(&$form)
    {
        $this->getFormCalled++;
        parent::getForm($form);
    }

    public function getEditForm(&$form)
    {
        $this->getEditFormCalled++;
        parent::getEditForm($form);
    }

    public function getActions(&$form)
    {
        $this->getActionsCalled++;
        parent::getActions($form);
    }
}

class MockIDataObjectSetDataSource implements IDataObjectSetDataSource {

    public $records = array();
    public $aggregate;
    public $group = array();
    public $canFilterBy = true;
    public $canSortBy = true;
    public $_dataClass;
    public $inExpansion;
    public $table;

    public function __construct($dataClass = "", $exp = null)
    {
        $this->_dataClass = $dataClass;
        $this->inExpansion = $exp;
    }

    protected function getListBy($records, $filter, $sort, $limit) {
        $copyRecords = array();
        foreach($records as $record) {
            $copyRecords[] = is_object($record) ? clone $record : $record;
        }

        $list = new ArrayList($copyRecords);
        if($filter) {
            $list = $list->filter($filter);
        }

        if($sort) {
            $list = $list->sort($sort);
        }

        if(isset($limit[0], $limit[1])) {
            $list = $list->getRange($limit[0], $limit[1]);
        }

        return $list;
    }

    public function getRecords($version, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array())
    {
        return $this->getListBy($this->records, $filter, $sort, $limit)->ToArray();
    }

    /**
     * gets specific aggregate like max, min, count, sum
     *
     * @param string $version
     * @param string|array $aggregate
     * @param string $aggregateField
     * @param bool $distinct
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param array $search
     * @param array $groupby
     * @return mixed
     */
    public function getAggregate($version, $aggregate, $aggregateField = "*", $distinct = false, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array(), $groupby = array())
    {
        if(strtolower($aggregate) == "count" && !isset($this->aggregate)) {
            return $this->getListBy($this->records, $filter, $sort, $limit)->count();
        }

        return $this->aggregate;
    }

    public function getGroupedRecords($version, $groupField, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array())
    {
        return $this->getListBy($this->group, $filter, $sort, $limit)->ToArray();
    }

    public function canFilterBy($field)
    {
        return $this->canFilterBy;
    }

    public function canSortBy($field)
    {
        return $this->canSortBy;
    }

    public function DataClass()
    {
        return $this->_dataClass;
    }

    public function getInExpansion()
    {
        return $this->inExpansion;
    }

    /**
     * @return string
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function baseTable()
    {
        return $this->table;
    }

    /**
     * @param array $manipulation
     * @param ManyMany_DataObjectSet $set
     * @param array $writeData array of versionid => boolean
     * @return mixed
     */
    public function onBeforeManipulateManyMany(&$manipulation, $set, $writeData)
    {
    }

    /**
     * @return void
     */
    public function clearCache()
    {
    }

    /**
     * @param Closure $closure
     * @return Closure
     */
    public function registerCacheCallback($closure) {
        return function() {
            return;
        };
    }


    /**
     * @param array $manipulation
     * @return bool
     */
    public function manipulate($manipulation)
    {
    }

    /**
     * @param $version
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param bool $forceClasses
     * @return SelectQuery
     */
    public function buildExtendedQuery($version, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $forceClasses = true)
    {
    }
}

class MockIModelSource implements IDataObjectSetModelSource {

    public $model;
    public $formCallback;
    public $getEditFormCallback;
    public $getActionsCallback;
    public $_dataClass;

    public function __construct($dataClass = "")
    {
        $this->_dataClass = $dataClass;
    }

    public function createNew($data = array())
    {
        return isset($this->model) ? $this->model : new ViewAccessableData($data);
    }

    public function getForm(&$form)
    {
        if(is_callable($this->formCallback)) {
            call_user_func_array($this->formCallback, array($form));
        }
    }

    public function getEditForm(&$form)
    {
        if(is_callable($this->getEditFormCallback)) {
            call_user_func_array($this->getEditFormCallback, array($form));
        }
    }

    public function getActions(&$form)
    {
        if(is_callable($this->getActionsCallback)) {
            call_user_func_array($this->getActionsCallback, array($form));
        }
    }

    public function DataClass()
    {
        return $this->_dataClass;
    }

    public function callExtending($method, &$p1 = null, &$p2 = null, &$p3 = null, &$p4 = null, &$p5 = null, &$p6 = null, &$p7 = null)
    {
    }
}

class MockDataObjectForDataObjectSet extends DataObject {}

class DumpDBElementPerson extends DataObject {

    public static function getDbDataSource($class)
    {
        return new MockIDataObjectSetDataSource($class);
    }

    public static function getModelDataSource($class)
    {
        return new MockIModelSource($class);
    }

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $age;

    /**
     * @var string 'M' or 'W'
     */
    public $gender;

    /**
     * DumpElementPerson constructor.
     * @param string $name
     * @param int $age
     * @param string $gender 'M' or 'W'
     */
    public function __construct($name = null, $age = null, $gender = null)
    {
        parent::__construct();

        if(is_array($name)) {
            $this->name = isset($name["name"]) ? $name["name"] : null;
            $this->age = isset($name["age"]) ? $name["age"] : null;
            $this->gender = isset($name["gender"]) ? $name["gender"] : null;
        }

        $this->name = $name;
        $this->age = $age;
        $this->gender = $gender;
    }

    public function &ToArray($additional_fields = array())
    {
        $data = array_merge(parent::ToArray($additional_fields), array(
            "name"      => $this->name,
            "age"       => $this->age,
            "gender"    => $this->gender
        ));
        return $data;
    }

    public function writeToDBInRepo($repository, $forceInsertNewRecord = false, $forceWrite = false, $writeType = 2, $history = true, $silent = false, $overrideCreated = false)
    {
        throw new Exception("Should not be written.");
    }
}
