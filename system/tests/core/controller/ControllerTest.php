<?php
namespace Goma\Test\Controller\ControllerTestENV;

use Controller;
use DataObject;
use GomaUnitTest;
use ReflectionMethod;
use Request;
use User;
use ViewAccessableData;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Controller-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ControllerTest extends GomaUnitTest {

	static $area = "Controller";
	/**
	 * name
	*/
	public $name = "Controller";

	public function testModelSaveManagementWithArray() {
		$view = new ViewAccessableData(array("test" => 3));

		$this->assertEqual($view->test, 3);

		$c = new Controller();

		$model = $c->getSafableModel(array("test" => 1, "blah" => 2, "blub" => "test"), $view);

		$this->assertEqual($view->test, 3);
		$this->assertEqual($model->test, 1);
		$this->assertEqual($model->blah, 2);
		$this->assertEqual($model->blub, "test");
	}

	public function testModelSaveManagementWithObject() {
		$view = new ViewAccessableData(array("test" => 3));
		$data = new ViewAccessableData(array("test" => 1, "blah" => 2, "blub" => "test"));

		$this->assertEqual($view->test, 3);

		$c = new Controller();

		$model = $c->getSafableModel($data, $view);

		$this->assertEqual($view->test, 3);
		$this->assertEqual($model->test, 1);
		$this->assertEqual($data->test, 1);
		$this->assertEqual($model->blah, 2);
		$this->assertEqual($model->blub, "test");
	}

	/**
	 *
	 */
	public function testModelInst() {
		$controller = new Controller();
		$controller->model = "user";

		$this->assertIsA($controller->modelInst(), "DataObjectSet");
		$this->assertEqual($controller->modelInst()->DataClass(), "user");
		$this->assertNull($this->unitTestGetSingleModel($controller));

		$controller->setRequest($request = new Request("get", "test"));
		$request->params["id"] = DataObject::get_one("user")->id;
		$this->assertIsA($this->unitTestGetSingleModel($controller), "user");
		$this->assertEqual($this->unitTestGetSingleModel($controller), DataObject::get_one("user"));

		$this->assertIsA($controller->modelInst("admin"), "admin");
		$this->assertEqual($controller->modelInst()->DataClass(), "admin");

		$controller->model = "admin";
		$controller->model_inst = null;

		$this->assertIsA($this->unitTestGetSingleModel($controller), "admin");
		$this->assertIsA($controller->modelInst("admin"), "admin");
		$this->assertEqual($controller->modelInst()->DataClass(), "admin");
	}

	public function unitTestGetSingleModel($controller) {
		$reflectionMethod = new ReflectionMethod("controller", "getSingleModel");
		$reflectionMethod->setAccessible(true);
		return $reflectionMethod->invoke($controller);
	}

	public function testGetSafableModel() {
		$controller = new Controller();
        $controller->setModelInst($user = new User(array(
            "id" => 2
        )));

        $this->assertEqual($controller->getSafableModel(array())->id, 0);
        $this->assertIsA($controller->getSafableModel(array()), User::class);

        $this->assertEqual($user, $controller->getSafableModel(array(), $user));

        $this->assertEqual($controller->getSafableModel(array(
            "test" => 123
        ))->test, 123);
	}

    /**
     * tests if Allowed Actions work
     * tests if RequestController is set correctly when just going trough the hierarchy with a subcontroller
     * @throws \Exception
     */
	public function testRequestControllerWithSubController() {
        $request = new Request(
            "POST",
            "test"
        );

        $controller = new TestControllerForRequestController();
        $this->assertEqual("SubSub", $controller->handleRequest($request));

        $this->assertEqual(2, count($request->getController()));
        $this->assertInstanceOf(TestControllerForRequestController::class, $request->getRequestController());
	}

    /**
     * tests if Allowed Actions work
     * tests if RequestController is set correctly when just going trough the hierarchy with a normal controller
     * @throws \Exception
     */
    public function testRequestControllerWithoutSubController() {
        $request = new Request(
            "POST",
            "lala"
        );

        $controller = new TestControllerForRequestController();
        $this->assertEqual("Sub", $controller->handleRequest($request));

        $this->assertEqual(2, count($request->getController()));
        $this->assertInstanceOf(TestSubControllerForRequestController::class, $request->getRequestController());
    }

    /**
     * tests if Allowed Actions work
     * tests if RequestController is set correctly when going through hierarchy and trying a second controller.
     * @throws \Exception
     */
    public function testRequestController() {
        $request = new Request(
            "GET",
            "test"
        );

        $controller = new TestControllerForRequestController();
        $this->assertEqual("Sub", $controller->handleRequest($request));

        $this->assertEqual(2, count($request->getController()));
        $this->assertInstanceOf(TestSubControllerForRequestController::class, $request->getRequestController());
    }
}

class TestControllerForRequestController extends \RequestHandler {
    public $allowed_actions = array(
        "test"
    );
    public function index() {
        $controller = new TestSubControllerForRequestController();
        return $controller->handleRequest($this->request);
    }

    public function lala() {
        return "lala";
    }

    public function test() {
        if($this->request->isPOST())  {
            $controller = new TestSubControllerWithSubForRequestController();
            return $controller->handleRequest($this->request, true);
        }
    }
}

class TestSubControllerForRequestController extends \RequestHandler {
    public function index() {
        return "Sub";
    }
}

class TestSubControllerWithSubForRequestController extends \RequestHandler {
    public function index()
    {
        return "SubSub";
    }
}
