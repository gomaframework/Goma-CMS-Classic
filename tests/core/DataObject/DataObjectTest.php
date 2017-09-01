<?php defined("IN_GOMA") OR die();

/**
 * Unit-Tests for DataObject-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DataObjectTests extends GomaUnitTest
{
    static $area = "NModel";
    /**
     * name
     */
    public $name = "DataObject";

    /**
     * write-test
     */
    public function testWriteToDB() {
        $this->unitTestWriteToDB(array(), "write");
        $this->unitTestWriteToDB(array(false, false, 2), "write");
        $this->unitTestWriteToDB(array(false, false, 1), "writeState");
        $this->unitTestWriteToDB(array(true, false, 1), "addState");
        $this->unitTestWriteToDB(array(true, false, 2), "add");

        $this->unitTestWriteToDB(array(false, true, 2), "write");
        $this->unitTestWriteToDB(array(false, true, 1), "writeState");
    }

    /**
     * @param $args
     * @param $expectedCall
     */
    public function unitTestWriteToDB($args, $expectedCall) {
        $oldRepo = Core::repository();
        $fakeRepo = new fakeRepo();
        Core::__setRepo($fakeRepo);

        $record = new MockWriteEntity();
        call_user_func_array(array($record, "writeToDB"), $args);

        $this->assertEqual($fakeRepo->lastMethod, $expectedCall);

        Core::__setRepo($oldRepo);
    }

    /**
     * tests if table exists for write-entity.
     */
    public function testTableExistence() {
        $this->assertTrue(gObject::instance("MockWriteEntity")->hasTable());
        $this->assertFalse(gObject::instance("MockWriteExtendedEntity")->hasTable());
        $this->assertTrue(gObject::instance("MockWriteEntity")->hasTable());
        $this->assertFalse(gObject::instance("MockWriteExtendedEntityWithFieldsInParent")->hasTable());
    }

    public function testArrayMerge() {
        $array1 = array(1, 2, 3);
        $array2 = array(4, 5, 6);

        $this->assertEqual(array_merge($array1, $array2), array(1, 2, 3, 4, 5, 6));
    }

    /**
     *
     */
    public function testPropExists() {
        $model = new MockWriteEntityWithFields(array(
            "test" => 1233
        ));
        $emptyModel = new MockWriteEntityWithFields(array(
            "test" => ""
        ));
        $this->assertFalse(property_exists($model, "test"));
        $this->assertTrue(isset($model->test));
        $this->assertTrue(isset($model["test"]));
        $this->assertFalse(isset($model->test2));
        $this->assertFalse(empty($model->test));
        $this->assertTrue(empty($emptyModel->test));
        $this->assertTrue(isset($emptyModel->test));
        $this->assertNotNull($model->test);
    }

    public function testModelCreation() {
        $this->assertNull(DataObject::getModelDataSource("DataObject"));
        $this->assertNull(DataObject::getDbDataSource("DataObject"));

        $this->assertInstanceOf(IDataObjectSetModelSource::class, DataObject::getModelDataSource("User"));
        $this->assertInstanceOf(IDataObjectSetDataSource::class, DataObject::getDbDataSource("User"));
    }

    public function testBooleanInit() {
        $model = new MockWriteEntityWithFields();
        $this->assertFalse($model->_bool);
    }

    public function testBooleanWriteAndGetFalse() {
        try {
            $model = new MockWriteEntityWithFields();
            $model->writeToDB(false, true);

            $gottenModel = DataObject::get_one(MockWriteEntityWithFields::class, array("id" => $model->id));
            $this->assertFalse($gottenModel->_bool);
            $this->assertIdentical(false, $gottenModel->_bool);
        } finally {
            if(isset($model)) {
                $model->remove(true);
            }
        }
    }

    public function testBooleanWriteAndGetTrue() {
        try {
            $model = new MockWriteEntityWithFields();
            $model->_bool = true;
            $model->writeToDB(false, true);

            $gottenModel = DataObject::get_one(MockWriteEntityWithFields::class, array("id" => $model->id));
            $this->assertTrue($gottenModel->_bool);
            $this->assertIdentical(true, $gottenModel->_bool);
        } finally {
            if(isset($model)) {
                $model->remove(true);
            }
        }
    }

    public function testCreateTimestamps() {
        $model = new MockWriteEntityWithFields();
        $this->assertEqual(time(), $model->created);
        $this->assertEqual(time(), $model->last_modified);
    }


    public function testCreateIDs() {
        $model = new MockWriteEntityWithFields();
        $this->assertIdentical(0, $model->id);
        $this->assertIdentical(0, $model->versionid);
    }

    public function testRightVersionsProp() {
        $classesUsingVersioned = array();
        foreach(ClassInfo::getChildren(DataObject::class) as $child) {
            if(!ClassInfo::isAbstract($child)) {
                $inst = new $child;
                if(property_exists($inst, "versioned") && $inst->versioned === true) {
                    $classesUsingVersioned[] = $child;
                }
            }
        }

        $this->assertEqual(array(), $classesUsingVersioned);
    }

    /**
     * @throws MySQLException
     */
    public function testSetVersionTypePublishOnWrite() {
        try {
            $entity = new MockWriteEntityWithFieldsVersioned();
            $entity->test = 2;
            $entity->writeToDB(false, true, 2);

            $this->assertEqual($entity->queryVersion, DataObject::VERSION_PUBLISHED);
        } finally {
            if(isset($entity)) {
                $entity->remove(true);
            }
        }
    }

    /**
     * @throws MySQLException
     */
    public function testSetVersionTypeStateOnWrite() {
        try {
            $entity = new MockWriteEntityWithFieldsVersioned();
            $entity->test = 2;
            $entity->writeToDB(false, true, 1);

            $this->assertEqual($entity->queryVersion, DataObject::VERSION_STATE);
        } finally {
            if(isset($entity)) {
                $entity->remove(true);
            }
        }
    }

    /**
     * @throws MySQLException
     */
    public function testSetVersionTypeStatePublishOnWrite() {
        try {
            $entity = new MockWriteEntityWithFieldsVersioned();
            $entity->test = 2;
            $entity->writeToDB(false, true, 2);

            $this->assertEqual($entity->queryVersion, DataObject::VERSION_PUBLISHED);

            $entity->writeToDB(false, true, 1);

            $this->assertEqual($entity->queryVersion, DataObject::VERSION_STATE);
        } finally {
            if(isset($entity)) {
                $entity->remove(true);
            }
        }
    }

    /**
     * tests update.
     */
    public function testDataObjectUpdate() {
        try {
            $entitiy = new MockWriteEntityWithFields();
            $entitiy->test = 2;
            $entitiy->writeToDB(false, true);

            $this->assertTrue(DataObject::update(MockWriteEntityWithFields::class, array("test" => 3), array("recordid" => $entitiy->id)));
            $entityFromDb = DataObject::get_by_id(MockWriteEntityWithFields::class, $entitiy->id);
            $this->assertEqual(3, $entityFromDb->test);
            $this->assertEqual(2, $entitiy->test);
        } finally {
            if($entitiy) {
                $entitiy->remove(true);
            }
        }
    }

    /**
     * tests updating an object silently and enforces it does not update last_modified.
     */
    public function testDataObjectUpdateSilent() {
        try {
            $entitiy = new MockWriteEntityWithFields();
            $entitiy->test = 2;
            $entitiy->writeToDB(false, true);

            $this->assertTrue(DataObject::update(MockWriteEntityWithFields::class, array("last_modified" => 1), array("recordid" => $entitiy->id)));
            $entityFromDb = DataObject::get_by_id(MockWriteEntityWithFields::class, $entitiy->id);
            $this->assertEqual(1, $entityFromDb->last_modified);

            $this->assertTrue(DataObject::update(MockWriteEntityWithFields::class, array("test" => 3), array("recordid" => $entitiy->id), true));
            $entityFromDb2 = DataObject::get_by_id(MockWriteEntityWithFields::class, $entitiy->id);
            $this->assertEqual(1, $entityFromDb2->last_modified);
            $this->assertEqual(3, $entityFromDb2->test);
        } finally {
            if($entitiy) {
                $entitiy->remove(true);
            }
        }
    }

    /**
     * tests updating an object silently and enforces it does not update last_modified.
     */
    public function testDataObjectUpdateNotSilent() {
        try {
            $entitiy = new MockWriteEntityWithFields();
            $entitiy->test = 2;
            $entitiy->writeToDB(false, true);

            $this->assertTrue(DataObject::update(MockWriteEntityWithFields::class, array("last_modified" => 1), array("recordid" => $entitiy->id)));
            $entityFromDb = DataObject::get_by_id(MockWriteEntityWithFields::class, $entitiy->id);
            $this->assertEqual(1, $entityFromDb->last_modified);

            $this->assertTrue(DataObject::update(MockWriteEntityWithFields::class, array("test" => 3), array("recordid" => $entitiy->id), false));
            $entityFromDb2 = DataObject::get_by_id(MockWriteEntityWithFields::class, $entitiy->id);
            $this->assertEqual(time(), $entityFromDb2->last_modified);
            $this->assertEqual(3, $entityFromDb2->test);
        } finally {
            if($entitiy) {
                $entitiy->remove(true);
            }
        }
    }
}

class MockWriteEntity extends DataObject {}

class MockWriteExtendedEntity extends MockWriteEntity {}

/**
 * Class MockWriteEntityWithFields
 * @property bool _bool
 */
class MockWriteEntityWithFields extends DataObject {
    static $db = array(
        "test" => "int(10)",
        "_bool" => "boolean"
    );
    static $search_fields = false;
}

/**
 * Class MockWriteEntityWithFields
 * @property bool _bool
 */
class MockWriteEntityWithFieldsVersioned extends DataObject {
    static $versions = true;
    static $db = array(
        "test" => "int(10)",
        "_bool" => "boolean"
    );
    static $search_fields = false;
}

class MockWriteExtendedEntityWithFieldsInParent extends MockWriteEntityWithFields {}

class fakeRepo extends  IModelRepository {

    /**
     * last method info.
     */
    public $lastMethod;

    /**
     * reads from a given model class.
     */
    public function read()
    {
        // TODO: Implement read() method.
        $this->lastMethod = "read";
    }

    /**
     * deletes a record.
     *
     * @param DataObject $record
     */
    public function delete($record)
    {
        // TODO: Implement delete() method.
        $this->lastMethod = "delete";
    }

    /**
     * writes a record in repository. it decides if record exists or not and updates or inserts.
     *
     * @param DataObject $record
     * @param bool if $forceWrite if to override permissions
     * @param bool $silent if to not update last-modified and editorid
     * @param bool $overrideCreated if to not force created and autorid to not be changed
     * @throws PermissionException
     */
    public function write($record, $forceWrite = false, $silent = false, $overrideCreated = false)
    {
        // TODO: Implement write() method.
        $this->lastMethod = "write";
    }

    /**
     * writes a record in repository as state. it decides if record exists or not and updates or inserts.
     *
     * @param DataObject $record
     * @param bool|if $forceWrite if to override permissions
     * @param bool $silent if to not update last-modified and editorid
     * @param bool $overrideCreated if to not force created and autorid to not be changed
     * @throws PermissionException
     */
    public function writeState($record, $forceWrite = false, $silent = false, $overrideCreated = false)
    {
        // TODO: Implement writeState() method.
        $this->lastMethod = "writeState";
    }

    /**
     * inserts record as new record.
     *
     * @param DataObject $record
     * @param bool $forceInsert
     * @param bool $silent
     * @param bool $overrideCreated
     * @throws PermissionException
     */
    public function add($record, $forceInsert = false, $silent = false, $overrideCreated = false)
    {
        // TODO: Implement add() method.
        $this->lastMethod = "add";
    }

    /**
     * inserts record as new record, but does not publish.
     *
     * @param DataObject $record
     * @param bool $forceInsert
     * @param bool $silent
     * @param bool $overrideCreated
     * @throws PermissionException
     */
    public function addState($record, $forceInsert = false, $silent = false, $overrideCreated = false)
    {
        // TODO: Implement addState() method.
        $this->lastMethod = "addState";
    }

    /**
     * builds up writer by parameters.
     *
     * @param DataObject $record
     * @param int $command
     * @param bool $silent
     * @param bool $overrideCreated
     * @param int $writeType
     * @param iDataBaseWriter $dbWriter
     * @return ModelWriter
     */
    public function buildWriter($record, $command, $silent, $overrideCreated, $writeType = self::WRITE_TYPE_PUBLISH, $dbWriter = null, $forceWrite = false)
    {
        // TODO: Implement buildWriter() method.
        $this->lastMethod = "buildWriter";
    }

    /**
     * @param DataObject $record
     * @param bool $forceWrite
     * @param bool $silent
     * @return void
     */
    public function publish($record, $forceWrite, $silent = false)
    {
        // TODO: Implement publish() method.
    }
}
