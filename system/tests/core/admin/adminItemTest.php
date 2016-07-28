<?php defined("IN_GOMA") OR die();

/**
 * Unit-Tests for AdminItem.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class AdminItemTest extends GomaUnitTest implements TestAble {

	/**
	 * area
	*/
	static $area = "Admin";

	/**
	 * internal name.
	*/
	public $name = "AdminItem";

	protected $item;
	protected $itemWithoutController;

	/**
	 * setup.
	*/
	public function setUp() {
		$reflectionMethod = new ReflectionMethod("adminItem", "initModelFromModels");
		$reflectionMethod->setAccessible(true);

		// we have admin-items, that manage models with controller...
		$this->item = new AdminItem();
		$this->item->models = array("Uploads");
		$reflectionMethod->invoke($this->item);

		// ... and without
		$this->itemWithoutController = new AdminItem();
		$this->itemWithoutController->models = array("Group");
		$reflectionMethod->invoke($this->itemWithoutController);
	}

	/**
	 * destruct.
	*/
	public function tearDown() {
		unset($this->item);
	}

	public function testModelControllerSystem() {
		$this->assertIsA($this->item->getControllerInst(), "Controller");
		$this->assertEqual($this->item->model(), "uploads");

		$this->assertNotNull($this->item->modelInst()->adminURI);

		// check if we can call controller functions
		$this->assertTrue($this->item->__cancall("handlerequest"));
		$this->assertTrue($this->item->__cancall("form"));
		$this->assertFalse($this->item->__cancall("myverystupidneverexistingfunction"));

		// checks for adminitems without controller
		$this->assertEqual($this->itemWithoutController->model(), "group");
		$this->assertNull($this->itemWithoutController->getControllerInst());
		$this->assertFalse($this->itemWithoutController->__cancall("handlerequest"));
	}
}