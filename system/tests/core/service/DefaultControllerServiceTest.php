<?php
namespace Goma\Test\Service;
use DataObject;
use DataObjectSet;
use Goma\Service\DefaultControllerService;
use Goma\Test\Model\DumpDBElementPerson;
use Goma\Test\Model\MockIDataObjectSetDataSource;
use LogicException;
use ViewAccessableData;

defined("IN_GOMA") OR die();
/**
 * Test DefaultControllerService
 *
 * @package SOREDI
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 *
 * @version 1.0
 */
class DefaultControllerServiceTest extends \GomaUnitTest {

    /**
     * @var DumpDBElementPerson
     */
    protected $simone;

    /**
     * @var DumpDBElementPerson
     */
    protected $daniel;

    public function setUp() {
        $this->simone = new DumpDBElementPerson("Simone", 21, "W");
        $this->simone->id = 1;

        $this->daniel = new DumpDBElementPerson("Daniel", 21, "M");
        $this->daniel->id = 2;
    }

    /**
     * init null.
     */
    public function testInitNull() {
        $defaultController = new DefaultControllerService();
        $this->assertInstanceOf(DefaultControllerService::class, $defaultController);
        $this->assertNull($defaultController->getModel());
        $this->assertEqual(\Core::repository(), $defaultController->repository());
    }

    /**
     * tests single model support
     */
    public function testServiceWithSingleModel() {
        $model = new \ViewAccessableData();
        $defaultController = new DefaultControllerService($model);
        $this->assertEqual($model, $defaultController->getModel());
        $this->assertEqual($model, $defaultController->getSingleModel());
        $this->assertEqual($model, $defaultController->getSingleModel("abc"));
    }

    /**
     * set
     */
    public function testServiceWithModelSetGetSafableModel() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->daniel,
            $this->simone
        );

        $defaultController = new DefaultControllerService($set);
        $this->assertEqual($set, $defaultController->getModel());
        $this->assertEqual($this->simone, $defaultController->getSafableModel(array(
            "id" => 1
        )));
        $this->assertEqual($this->daniel, $defaultController->getSafableModel(array(
            "id" => 2
        )));
    }


    /**
     * set
     */
    public function testServiceWithModelSet() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $source->records = array(
            $this->daniel,
            $this->simone
        );

        $defaultController = new DefaultControllerService($set);

        $this->assertEqual($set, $defaultController->getModel());
        $this->assertNull($defaultController->getSingleModel());
        $this->assertEqual($this->simone, $defaultController->getSingleModel(1));
        $this->assertEqual($this->daniel, $defaultController->getSingleModel(2));
    }

    /**
     *
     */
    public function testWriteToDB() {
        $testModel = new writeToDBInRepoMockModel();
        $defaultController = new DefaultControllerService($testModel);

        $this->assertNull($testModel->methodCalledArgs);
        $defaultController->saveModel($testModel, array("blub" => 1));

        $this->assertNotNull($testModel->methodCalledArgs);
        $this->assertEqual(1, $testModel->blub);
    }

    /**
     * tests apply data to model
     */
    public function testModelSaveManagementWithArray() {
        $view = new ViewAccessableData(array("test" => 3));

        $this->assertEqual(3, $view->test);

        $controller = new DefaultControllerService();

        $model = $controller->applyDataToModel($view, array("test" => 1, "blah" => 2, "blub" => "test"));

        $this->assertEqual(1, $view->test);
        $this->assertEqual(1, $model->test);
        $this->assertEqual(2, $model->blah);
        $this->assertEqual("test", $model->blub);
    }

    /**
     * tests apply data to model
     */
    public function testModelSaveManagementWithObject() {
        $view = new ViewAccessableData(array("test" => 3));

        $this->assertEqual(3, $view->test);

        $controller = new DefaultControllerService();

        $model = $controller->applyDataToModel($view,
            new ViewAccessableData(array("test" => 1, "blah" => 2, "blub" => "test")));

        $this->assertEqual(1, $view->test);
        $this->assertEqual(1, $model->test);
        $this->assertEqual(2, $model->blah);
        $this->assertEqual("test", $model->blub);
    }

    /**
     * tests apply data to model
     */
    public function testModelSaveManagementWithArrayClone() {
        $view = new ViewAccessableData(array("test" => 3));

        $this->assertEqual(3, $view->test);

        $controller = new DefaultControllerService();

        $model = $controller->applyDataToModel(clone $view, array("test" => 1, "blah" => 2, "blub" => "test"));

        $this->assertEqual(3, $view->test);
        $this->assertEqual(1, $model->test);
        $this->assertEqual(2, $model->blah);
        $this->assertEqual("test", $model->blub);
    }

    /**
     * tests throws exception for getSafableModel
     */
    public function testGetSafableModel() {
        $this->assertThrows(function(){
            $controller = new DefaultControllerService();
            $controller->getSafableModel(array());
        }, LogicException::class);
    }

    /**
     * tests get safable model
     */
    public function testModelGetSafableModel() {
        $view = new ViewAccessableData(array("test" => 3));

        $this->assertEqual(3, $view->test);

        $controller = new DefaultControllerService();

        $model = $controller->getSafableModel(array("test" => 1, "blah" => 2, "blub" => "test"), $view);

        $this->assertEqual(3, $view->test);
        $this->assertEqual(3, $model->test);
        $this->assertNull($model->blah);
        $this->assertNull($model->blub);
    }
}

class writeToDBInRepoMockModel extends \ViewAccessableData {
    /**
     * @var array|null
     */
    public $methodCalledArgs;

    public function writeToDBInRepo() {
        $this->methodCalledArgs = func_get_args();
    }
}
