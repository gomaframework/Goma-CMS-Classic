<?php
namespace Goma\Test\Controller\ControllerTestENV;

use Controller;
use DataObject;
use DataObjectSet;
use Goma\Test\Model\DumpDBElementPerson;
use Goma\Test\Model\MockIDataObjectSetDataSource;
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
	 *
	 */
	public function testGuessModelInst() {
		$controller = new UserController();

		$this->assertIsA($controller->modelInst(), "DataObjectSet");
		$this->assertEqual($controller->modelInst()->DataClass(), "user");
		$this->assertNull($this->unitTestGetSingleModel($controller));
	}

	/**
	 *
	 */
	public function testGetSingleModelFromRequest() {
		$controller = new UserController();

		$controller->setRequest($request = new Request("get", "test"));
		$user =  DataObject::get_one("user");
		$request->params["id"] = $user->id;
		$this->assertIsA($this->unitTestGetSingleModel($controller), "user");
		$this->assertEqual($this->unitTestGetSingleModel($controller), $user);;

		$this->assertIsA($controller->modelInst(), "DataObjectSet");
		$this->assertEqual($controller->modelInst()->DataClass(), "user");
	}

	/**
	 * checks if we can change the "single model"
	 */
	public function testSwitchGetSingleModel() {
		$controller = new UserController();

		$controller->setRequest($request = new Request("get", "test"));
		$user =  DataObject::get_one("user");
		$request->params["id"] = $user->id;
		$this->assertIsA($this->unitTestGetSingleModel($controller), "user");

		$controller->setModelInst(new \admin());

		$this->assertIsA($this->unitTestGetSingleModel($controller), "admin");
		$this->assertIsA($controller->modelInst(), "admin");
		$this->assertEqual($controller->modelInst()->DataClass(), "admin");
	}

	/**
	 * @param $controller
	 * @return mixed
	 */
	public function unitTestGetSingleModel($controller) {
		$reflectionMethod = new ReflectionMethod("controller", "getSingleModel");
		$reflectionMethod->setAccessible(true);
		return $reflectionMethod->invoke($controller);
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
	 * tests controllerIsNextToRootOfType
	 */
	public function testcontrollerIsNextToRootOfTypeTrue() {
		$controller = new TestSubClassController();
		$controller2 = new TestSubControllerWithSubForRequestController();

		$request = new Request("get", "lala");
		$controller->Init($request);
		$controller2->Init($request);

		$this->assertTrue($controller->controllerIsNextToRootOfType(TestSubControllerWithSubForRequestController::class));
		$this->assertFalse($controller2->controllerIsNextToRootOfType(TestSubControllerWithSubForRequestController::class));
	}

	/**
	 * tests controllerIsNextToRootOfType
	 */
	public function testcontrollerIsNextToRootOfTypeFalse() {
		$controller = new TestSubClassController();
		$controller2 = new TestSubControllerWithSubForRequestController();

		$request = new Request("get", "lala");
		$controller2->Init($request);
		$controller->Init($request);

		$this->assertTrue($controller->controllerIsNextToRootOfType(TestSubClassController::class));
		$this->assertFalse($controller->controllerIsNextToRootOfType(TestSubControllerWithSubForRequestController::class));
		$this->assertTrue($controller2->controllerIsNextToRootOfType(TestSubControllerWithSubForRequestController::class));
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

	/**
	 * tests request for clean index
	 * @throws \Exception
	 */
	public function testRequestForIndexClean() {
		$request = new Request(
			"GET",
			"test"
		);

		$controller = new TestSubControllerForRequestController();
		$this->assertEqual("Sub", $controller->handleRequest($request));
		$this->assertEqual("test", $request->remaining());
	}

	/**
	 * tests basic function from that method
	 */
	public function testgetActionCompleteText() {
		$controller = new Controller();
		$reflectionMethod = new ReflectionMethod(Controller::class, "getActionCompleteText");
		$reflectionMethod->setAccessible(true);
		$this->assertEqual(lang("successful_published", "The entry was successfully published."),
			$reflectionMethod->invoke($controller, "publish_success")
		);
	}

	public function testRecordCount() {
		$set = new DataObjectSet(DumpDBElementPerson::class);
		$set->setVersion(DataObject::VERSION_PUBLISHED);

		/** @var MockIDataObjectSetDataSource $source */
		$source = $set->getDbDataSource();

		$source->records = array(
			$this->daniel,
			$this->simone
		);

		TestIndexCountController::$indexCount = 0;

		$controller = new TestIndexCountController();
		$controller->setModelInst($set);

		$request = new Request("get", "record/1");
		$this->assertEqual("lala", $controller->handleRequest($request));
		$this->assertEqual(1, TestIndexCountController::$indexCount);
	}
}

class TestIndexCountController extends Controller {
	static $indexCount = 0;

	public function index()
	{
		self::$indexCount++;
		return "lala";
	}
}

class TestControllerForRequestController extends \RequestHandler {
    public $allowed_actions = array(
        "test"
    );
    public function index() {
        $controller = new TestSubControllerForRequestController();
        return $controller->handleRequest($this->request, $this->isSubController());
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

class TestSubClassController extends TestSubControllerWithSubForRequestController {

}

class UserController extends Controller {

}

