<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Model-Writer.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class ModelWriterTests extends GomaUnitTest implements TestAble
{
    /**
     * area
     */
    static $area = "Transactions";

    /**
     * internal name.
     */
    public $name = "ModelWriter";

    /**
     * tests if change-property is correctly evaluated.
     */
    public function testChanged() {
        $this->assertTrue($this->unitTestChanged(array(
            "test" => 1,
            "last_modified" => time() - 10,
            "created"       => time() - 20,
            "autorid"       => 2,
            "editorid"      => 3
        ), array(
            "test" => 1,
            "last_modified" => time(),
            "created"       => time() - 20,
            "autorid"       => 2,
            "editorid"      => 3
        )));

        $this->assertFalse($this->unitTestChanged(array(
            "test" => 1,
            "last_modified" => time() - 10,
            "created"       => time() - 20,
            "autorid"       => 2,
            "editorid"      => 3
        ), array(
            "test" => "1",
            "last_modified" => time() - 10,
            "created"       => time() - 20,
            "autorid"       => 2,
            "editorid"      => "3"
        )));

        $this->assertFalse($this->unitTestChanged(array(
            "test" => 1,
            "last_modified" => time() - 10,
            "created"       => time() - 20,
            "autorid"       => 2,
            "editorid"      => 3
        ), array(
            "test" => "1",
        )));

        $this->assertTrue($this->unitTestChanged(array(
            "test" => 1,
            "last_modified" => time() - 10,
            "created"       => time() - 20,
            "autorid"       => 2,
            "editorid"      => 3
        ), array(
            "test" => "2",
        )));

        $this->assertTrue($this->unitTestChanged(array(
            "test" => 1,
            "last_modified" => time() - 10,
            "created"       => time() - 20,
            "autorid"       => 2,
            "editorid"      => 3
        ), array(
            "test" => "1",
            "autorid" => 3
        )));
    }

    /**
     * @param $mockData
     * @param $newData
     * @return mixed
     */
    protected function unitTestChanged($mockData, $newData) {
        $mockObject = new MockUpdatableGObject();
        $mockObject->data = $mockData;

        $newDataObject = new MockUpdatableGObject();
        $newDataObject->data = $newData;

        $writer = new ModelWriter($newDataObject, ModelRepository::COMMAND_TYPE_UPDATE, $mockObject, new MockDBRepository(), new MockDBWriter());

        $reflectionMethod = new ReflectionMethod("ModelWriter", "checkForChanges");
        $reflectionMethod->setAccessible(true);

        $reflectionMethodData = new ReflectionMethod("ModelWriter", "gatherDataToWrite");
        $reflectionMethodData->setAccessible(true);
        $reflectionMethodData->invoke($writer);

        $this->assertEqual($newDataObject->onBeforeWriteFired, 0);

        return $reflectionMethod->invoke($writer);
    }

    /**
     * Tests if write-method fires the correct events always once.
     */
    public function testWrite() {
        try {
            ModelWriterTestExtensionForEvents::$checkLogic = true;

            $mockData = array("test" => 1);
            $newData = array("test" => 2);
            $mockObject = new MockUpdatableGObject();
            $mockObject->checkLogic = true;
            $mockObject->data = $mockData;

            $newDataObject = new MockUpdatableGObject();
            $mockObject->checkLogic = false;
            $newDataObject->data = $newData;

            $writer = new ModelWriter(
                $newDataObject,
                ModelRepository::COMMAND_TYPE_UPDATE,
                $mockObject,
                new MockDBRepository(),
                new MockDBWriter()
            );
            ModelWriterTestExtensionForEvents::$checkLogic = true;
            ModelWriterTestExtensionForEvents::clear();
            $this->assertEqual(0, ModelWriterTestExtensionForEvents::$onBeforeWriteFired);
            $writer->write();

            $this->assertEqual(0, $mockObject->onBeforeWriteFired);
            $this->assertEqual(0, $mockObject->onAfterWriteFired);

            $this->assertEqual(1, $newDataObject->onBeforeWriteFired);
            $this->assertEqual(1, $newDataObject->onAfterWriteFired);

            /** @var ModelWriterTestExtensionForEvents $extInstance */
            $this->assertEqual(1, ModelWriterTestExtensionForEvents::$onBeforeWriteFired);
            $this->assertEqual(1, ModelWriterTestExtensionForEvents::$onAfterWriteFired);
            $this->assertEqual(1, ModelWriterTestExtensionForEvents::$onBeforeDBWriterFired);
            $this->assertEqual(1, ModelWriterTestExtensionForEvents::$gatherDataToWrite);
        } finally {
            ModelWriterTestExtensionForEvents::$checkLogic = false;
        }
    }

    /**
     * tests if valueMatches works.
     */
    public function testvalueMatches() {
        $this->assertTrue($this->unittestvalueMatches("1", 1));
        $this->assertTrue($this->unittestvalueMatches("1", "1"));
        $this->assertTrue($this->unittestvalueMatches("2", "2"));
        $this->assertTrue($this->unittestvalueMatches(2, "2"));
        $this->assertTrue($this->unittestvalueMatches(1, true));
        $this->assertTrue($this->unittestvalueMatches(0, false));
        $this->assertTrue($this->unittestvalueMatches(0, ""));
        $this->assertTrue($this->unittestvalueMatches(false, ""));
        $this->assertTrue($this->unittestvalueMatches(true, "2"));

        $this->assertFalse($this->unittestvalueMatches(22, "2"));
        $this->assertFalse($this->unittestvalueMatches(1, ""));
        $this->assertFalse($this->unittestvalueMatches(new StdClass("2"), "2"));
    }

    protected function unittestvalueMatches($var1, $var2) {
        $reflectionMethod = new ReflectionMethod("ModelWriter", "valueMatches");
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invoke(null, $var1, $var2);
    }

    public function testPermissionCalling() {
        $this->assertEqual(
            $this->unittestPermissionCalling(IModelRepository::COMMAND_TYPE_INSERT, IModelRepository::WRITE_TYPE_PUBLISH),
            array(ModelPermissionManager::PERMISSION_TYPE_INSERT, ModelPermissionManager::PERMISSION_TYPE_PUBLISH)
        );

        $this->assertEqual(
            $this->unittestPermissionCalling(IModelRepository::COMMAND_TYPE_PUBLISH, IModelRepository::WRITE_TYPE_PUBLISH),
            array(ModelPermissionManager::PERMISSION_TYPE_WRITE, ModelPermissionManager::PERMISSION_TYPE_PUBLISH)
        );

        $this->assertEqual(
            $this->unittestPermissionCalling(IModelRepository::COMMAND_TYPE_INSERT, IModelRepository::WRITE_TYPE_SAVE),
            array(ModelPermissionManager::PERMISSION_TYPE_INSERT)
        );

        $this->assertEqual(
            $this->unittestPermissionCalling(IModelRepository::COMMAND_TYPE_UPDATE, IModelRepository::WRITE_TYPE_PUBLISH),
            array(ModelPermissionManager::PERMISSION_TYPE_WRITE, ModelPermissionManager::PERMISSION_TYPE_PUBLISH)
        );

        $this->assertEqual(
            $this->unittestPermissionCalling(IModelRepository::COMMAND_TYPE_UPDATE, IModelRepository::WRITE_TYPE_SAVE),
            array(ModelPermissionManager::PERMISSION_TYPE_WRITE)
        );

        try {
            $this->unittestPermissionCalling(IModelRepository::COMMAND_TYPE_UPDATE, IModelRepository::WRITE_TYPE_SAVE, false);
            $this->assertFalse(true);
        } catch(Exception $e) {
            $this->assertIsA($e, "PermissionException");
        }
    }

    public function unittestPermissionCalling($commandType, $writeType, $validate = true) {
        $model = new MockUpdatableGObject();
        $model->validate = $validate;
        $modelWriter = new ModelWriter($model, $commandType, $model, new MockDBRepository(), new MockDBWriter());
        $modelWriter->setWriteType($writeType);

        $modelWriter->validatePermission();

        return $model->getCalledPermissions();
    }

    /**
     * tests if events for update are fired in order like defined at https://confluence.goma-cms.org/display/GOMA/Model+Events.
     * writeType = publish.
     *
     * 1. onBeforeWrite
     * 2. onBeforePublish
     * 3. onAfterWrite
     * 4. onAfterPublish
     */
    public function testEventsForUpdatePublish() {
        $model = new MockUpdatableGObject();
        $model->data = array("a" => "b");
        $modelWriter = new ModelWriter($model, IModelRepository::COMMAND_TYPE_UPDATE, null, new MockDBRepository(), new MockDBWriter());
        $modelWriter->write();

        $this->assertEqual(1, $model->onBeforeWriteFired);
        $this->assertEqual(1, $model->onBeforePublishFired);
        $this->assertEqual(1, $model->onAfterWriteFired);
        $this->assertEqual(1, $model->onAfterPublishFired);
    }

    /**
     * tests if events for update are fired in order like defined at https://confluence.goma-cms.org/display/GOMA/Model+Events.
     * writeType = write.
     *
     * 1. onBeforeWrite
     * 2. onAfterWrite
     */
    public function testEventsForUpdateState() {
        $model = new MockUpdatableGObject();
        $model->data = array("a" => "b");
        $modelWriter = new ModelWriter($model, IModelRepository::COMMAND_TYPE_UPDATE, null, new MockDBRepository(), new MockDBWriter());
        $modelWriter->setWriteType(IModelRepository::WRITE_TYPE_SAVE);
        $modelWriter->write();

        $this->assertEqual(1, $model->onBeforeWriteFired);
        $this->assertEqual(0, $model->onBeforePublishFired);
        $this->assertEqual(1, $model->onAfterWriteFired);
        $this->assertEqual(0, $model->onAfterPublishFired);
    }

    /**
     * tests if callModelExtending passes variables by reference.
     */
    public function testCallModelExtendingReference() {
        $model = new MockUpdatableGObject();
        $modelWriter = new ModelWriter($model, IModelRepository::COMMAND_TYPE_UPDATE, null, new MockDBRepository(), new MockDBWriter());
        $var = "abc";
        $modelWriter->callModelExtending("changeVar", $var);
        $this->assertTrue($var);
    }
}

class MockUpdatableGObject extends gObject {
    public $data;

    public $stateid = 1;
    public $publishedid = 1;
    public $versionid = 1;
    public $id = 1;
    public $onBeforeWriteFired = 0;
    public $onBeforePublishFired = 0;
    public $onAfterWriteFired = 0;
    public $onAfterPublishFired = 0;
    public $checkLogic = false;
    protected $calledPermissions = array();
    public $validate = true;

    static $history = false;

    public function can($permission) {
        $this->calledPermissions[] = strtolower($permission);
        return $this->validate;
    }

    /**
     * @return array
     */
    public function getCalledPermissions()
    {
        return $this->calledPermissions;
    }

    public function clearPermissions() {
        $this->calledPermissions = array();
    }

    public function __get($k) {
        return "";
    }

    public function ToArray() {
        return $this->data;
    }

    public function onBeforeWrite() {
        $this->onBeforeWriteFired++;
    }

    public function onAfterWrite() {
        if($this->checkLogic && $this->onBeforeWriteFired == $this->onAfterWriteFired) {
            throw new LogicException("OnBeforeWrite must be fired before onAfterWrite");
        }
        $this->onAfterWriteFired++;
    }

    public function onBeforePublish() {
        if($this->onBeforeWriteFired == $this->onBeforePublishFired) {
            throw new LogicException("OnBeforeWrite must be fired before onBeforePublish");
        }

        $this->onBeforePublishFired++;
    }

    public function onAfterPublish() {
        if($this->checkLogic && $this->onAfterWriteFired == $this->onAfterPublishFired) {
            throw new LogicException("onAfterWrite must be fired before onAfterPublish");
        }
        $this->onAfterPublishFired++;
    }

    public function workWithExtensionInstance($extensionClassName, $callback) {
        
    }

    public function __call($key, $val) {
        return array();
    }

    /**
     * for reference test.
     *
     * @param ModelWriter $modelWriter
     * @param bool $blub
     */
    public function changeVar($modelWriter, &$blub) {
        $blub = true;
    }
}

class ModelWriterTestExtensionForEvents extends Extension {
    public static $onBeforeWriteFired = 0;
    public static $onAfterWriteFired = 0;
    public static $gatherDataToWrite = 0;
    public static $onBeforeDBWriterFired = 0;
    public static $checkLogic = false;
    protected $calledPermissions = array();

    public static function clear() {
        self::$onBeforeWriteFired = 0;
        self::$onAfterWriteFired = 0;
        self::$gatherDataToWrite = 0;
        self::$onBeforeDBWriterFired = 0;
    }

    public function gatherDataToWrite() {
        if(self::$checkLogic && self::$gatherDataToWrite == self::$onBeforeWriteFired) {
            throw new LogicException("onBeforeWrite must be fired before onGatherDataToWrite");
        }
        self::$gatherDataToWrite++;
    }

    public function onBeforeWrite() {
        self::$onBeforeWriteFired++;
    }

    public function onBeforeDBWriter() {
        if(self::$checkLogic && self::$onBeforeDBWriterFired == self::$gatherDataToWrite) {
            throw new LogicException("gatherDataToWrite must be fired before onBeforeDBWrite");
        }
        self::$onBeforeDBWriterFired++;
    }

    public function onAfterWrite() {
        if(self::$checkLogic && self::$onBeforeDBWriterFired == self::$onAfterWriteFired) {
            throw new LogicException("onBeforeDBWriter must be fired before onAfterWrite");
        }
        self::$onAfterWriteFired++;
    }
}
gObject::extend("ModelWriter", "ModelWriterTestExtensionForEvents");

class MockDBRepository extends  IModelRepository {

    /**
     * reads from a given model class.
     */
    public function read()
    {
        // TODO: Implement read() method.
    }

    /**
     * deletes a record.
     *
     * @param DataObject $record
     */
    public function delete($record)
    {
        // TODO: Implement delete() method.
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

class MockDBWriter implements iDataBaseWriter {

    /**
     * sets Writer-Object.
     *
     * @param ModelWriter $writer
     */
    public function setWriter($writer)
    {
        // TODO: Implement setWriter() method.
    }

    /**
     * writes data of Writer to Database.
     */
    public function write()
    {
        // TODO: Implement write() method.
    }

    /**
     * validates.
     */
    public function validate()
    {
        return true;
    }

    /**
     * tries to find recordid in versions of state-table.
     *
     * @param int $recordid
     * @return Tuple<publishedid, stateid>
     */
    public function findStateRow($recordid)
    {
        return new Tuple(1, 1);
    }

    /**
     * publish.
     */
    public function publish()
    {
        // TODO: Implement publish() method.
    }
}
