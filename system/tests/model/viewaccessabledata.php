<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for ViewAccessableData.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class ViewAccessableDataTest extends GomaUnitTest implements TestAble {
	
	/**
	 * area
	*/
	static $area = "viewmodel";

	/**
	 * internal name.
	*/
	public $name = "ViewAccessableData";

	/**
	 * checks for data
	*/
	public function testCheckData() {
		$view = new viewaccessableData(array("id" => 1, "data" => 2, "title" => 3));
		$this->assertTrue($view->bool());
	}
	
	/**
	 * checks for no data
	*/
	public function testCheckNoData() {
		$view = new viewaccessableData();
		$this->assertFalse($view->bool());
	}

	/**
	 * test customise.
	*/
	public function testCustomise() {
		$data = new ViewAccessableData(array("blah" => "blub"));
		$this->assertEqual($data->blah, "blub");
		$this->assertEqual($data->blah_cust, null);
		$this->assertEqual($data->blub, null);

		// customise this
		$customised = $data->customise(array("blah_cust" => "test"));
		$this->assertEqual($data->blah_cust, "test");
		$this->assertEqual($customised->blah_cust, "test");
		$this->assertEqual($customised->blub, null);

		$newCustomised = $data->customisedObject(array("blub" => "blah"));
		$this->assertEqual($data->blah_cust, "test");
		$this->assertEqual($data->blub, null);
		$this->assertEqual($newCustomised->blah_cust, "test");
		$this->assertEqual($newCustomised->blub, "blah");
	}
	
	/**
	 * checks for reset
	*/
	public function testReset() {
		$view = new viewaccessableData(array("id" => 1, "data" => 2, "title" => 3));
		$this->assertTrue($view->bool());
		$view->reset();
		$this->assertFalse($view->bool());
	}
	
	/**
	 * test for ToArray
	*/
	public function testToArray() {
		$data = array("id" => 1, "data" => 2, "title" => 3);
		$view = new viewaccessableData($data);
		$this->assertEqual($view->toArray(), $data);
	}
	
	/**
	 * checks if changes work
	*/
	public function testChanged() {
		$data = array("id" => 1, "data" => 2, "title" => 3);
		$view = new ViewAccessableData($data);
		$this->assertFalse($view->wasChanged());
		$view->title = 4;
		$view->assertTrue($view->wasChanged());
	}
	
	/**
	 * tests doObject
	*/
	public function testDoObject() {
		$data = array("id" => 1, "data" => 2, "title" => 3);
		$view = new ViewAccessableData($data);
		$this->assertIsA($view->doObject("data"), "DBField");
	}

	/**
	 * tests getOffset
	*/
	public function testGetOffset() {
		$htmlText = "<p>test</p>";
		$data = array("blub" => 1, "blah" => "blub", "data" => "test", "html" => $htmlText, "htmltext" => $htmlText);
		ViewAccessableData::$casting["htmltext"] = "varchar";
		$view = new ViewAccessableData($data);

		$this->assertEqual($view->blub, 1);
		$this->assertEqual($view->data, "test");
		$this->assertEqual($view->blah, "blub");
		$this->assertEqual($view->getOffset("blah"), "blub");
		$this->assertEqual($view->blah()->raw(), "blub");
		$this->assertEqual($view->blah()->text(), "blub");

		$this->assertEqual($view->html, $htmlText);
		$this->assertEqual($view->html()->raw(), $htmlText);
		$this->assertEqual($view->html()->text(), convert::raw2text($htmlText));
		$this->assertEqual((string) $view->html(), $htmlText);
		$this->assertEqual($view->html()->forTemplate(), $htmlText);
		$this->assertEqual($view->getTemplateVar("HTml"), $htmlText);

		// some checks for automatic XSS-Prevention of Varchar
		$this->assertEqual($view->htmltext()->forTemplate(), convert::raw2text($htmlText));
		$this->assertEqual($view->getTemplateVar("HTMLTExt"), convert::raw2text($htmlText));
		$this->assertEqual($view->htmltext()->raw(), $htmlText);
		$this->assertEqual((string) $view->htmltext(), convert::raw2text($htmlText));

		// this is not last viewmodel of dataobjectset.
		$this->assertFalse($view->last());
		$this->assertEqual($view->this(), $view);

		$this->assertEqual($view->_server_request_uri, $_SERVER["REQUEST_URI"]);

		$this->assertEqual($view->getOffset("BLAh"), "blub");

		$cust = array("haha" => 1, "blub" => 3);
		$view->customise($cust);
		$this->assertEqual($view->blub, 3);
		$this->assertEqual($view->haha, 1);
		$this->assertEqual($view->getCustomisation(), $cust);

		$c = $view->getObjectWithoutCustomisation();
		$this->assertEqual($c->blub, 1);
		$this->assertEqual($view->blub, 3);

		$testClass = new TestViewClassMethod(array("blub" => 2));
		$this->assertEqual($testClass->blub, 2);
		$this->assertEqual($testClass->myLittleValue, "val");
		$this->assertEqual($testClass->myLittleTest, "val");
		$this->assertEqual($testClass->myLittleValue(), "val");
		$this->assertIsA($testClass->myLittleTest(), "DBField");
		$this->assertEqual($testClass->__call("myLittleValue", array()), "val");
		$this->assertTrue(Object::method_exists($testClass,"myLittleValue"));
		$this->assertTrue(Object::method_exists($testClass,"myLittleTest"));
		$this->assertFalse(Object::method_exists($testClass->classname,"myLittleTest"));
		$this->assertEqual($testClass->getMyLittleTest, "val");
	}
}

class TestViewClassMethod extends ViewAccessableData {
	public function myLittleValue() {
		return "val";
	}

	public function getMyLittleTest() {
		return "val";
	}
}