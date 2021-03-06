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
	static $area = "NModel";

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
	 * tests if getTemplateVar("this") returns print_r($data, true)
	 */
	public function testGetTemplateVarThisPrintsR() {
		$data = new ViewAccessableData(array("blah" => "blub", "test" => $test = new TestClassWithForTemplate(), "std" => $std = new StdClass()));

		$this->assertEqual($data->this(), $data);
		$this->assertEqual($data->getTemplateVar("this"), print_r($data, true));
	}

	/**
	 * tests if this.blah returns blub
	 */
	public function testGetTemplateVarString() {
		$data = new ViewAccessableData(array("blah" => "blub", "test" => $test = new TestClassWithForTemplate(), "std" => $std = new StdClass()));
		$this->assertEqual($data->getTemplateVar("this.blah"), "blub");
	}


	/**
	 * tests if __toString is called.
	 */
	public function testGetTemplateVarToString() {
		$data = new ViewAccessableData(array("blah" => "blub", "test" => $test = new TestClassWithForTemplateToString(), "std" => $std = new StdClass()));
		$test->templateInfo = "blub123";
		$this->assertEqual($data->getTemplateVar("test"), "blub123");
	}

	/**
	 * tests if object with defined method forTemplate(), that that method is be called.
	 * It tests also if getTemplateVar is called when remaining stuff is there and gets further information and can handle rest of query by itself.
	 */
	public function testGetTemplateVarCallsGetTemplateVarOnObject() {
		$data = new ViewAccessableData(array("blah" => "blub",
											 "test" => $test = new TestClassWithForTemplate(), "std" => $std = new StdClass()));

		$test->templateInfo = "blub";
		$this->assertEqual($data->getTemplateVar("test"), "blub");
		$this->assertEqual($data->getTemplateVar("test.blah"), "blah");
		$this->assertEqual($data->test, $test);
	}

	/**
	 * test if StdClass is converted to string with print_r by getTemplateVar.
	 */
	public function testGetStdFromTemplateVar() {
		$data = new ViewAccessableData(array("blah" => "blub",
											 "test" => $test = new TestClassWithForTemplate(), "std" => $std = new StdClass()));

		$this->assertEqual($data->getTemplateVar("std"), print_r($std, true));
		$this->assertEqual($data->getTemplateVar("std.blub"), print_r($std, true));
	}

	/**
	 * test if StdClass is converted to string with print_r by getTemplateVar
	 * if casting is set to Varchar.
	 */
	public function testGetStdFromTemplateVarWithCasting() {
		$data = new ViewAccessableData(array("blah" => "blub",
											 "test" => $test = new TestClassWithForTemplate(), "std" => $std = new StdClass()));

		ViewAccessableData::$casting["std"] = "Varchar";

		$this->assertEqual($data->getTemplateVar("std"), print_r($std, true));
		$this->assertEqual($data->getTemplateVar("std.blub"), print_r($std, true));
	}

	/**
	 * tests getTemplateVar with Recursive ViewAccessableData Objects if this is overwritten with customsed.
	 * In that case this.test should be the test property on the submodel.
	 */
	public function testRecursiveGetTemplateVar() {
		$data = new ViewAccessableData(array("blah" => "blub",
											 "test" => $test = new TestClassWithForTemplate(), "std" => $std = new StdClass()));

		$testModel = new ViewAccessableData(array(
			"test" => 123
		));

		$data->customised["this"] = $testModel;
		$this->assertEqual($data->getTemplateVar("this"), print_r($testModel, true));
		$this->assertEqual($data->getTemplateVar("this.test"), $testModel->test);
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
		$this->assertEqual($view->this(), $view);

		$this->assertEqual($view->_server_request_uri, $_SERVER["REQUEST_URI"]);

		$this->assertEqual($view->getOffset("BLAh"), "blub");

		$cust = array("haha" => 1, "blub" => 3);
		$view->customise($cust);
		$this->assertEqual($view->blub, 3);
		$this->assertEqual($view->haha, 1);
		$this->assertEqual($view->getCustomisation(), $cust);

		$objectWithoutCustomisation = $view->getObjectWithoutCustomisation();
		$this->assertEqual($objectWithoutCustomisation->blub, 1);
		$this->assertEqual($view->blub, 3);

		// it is customised
		unset($view->blub);
		$this->assertEqual($view->blub, 3);

		unset($objectWithoutCustomisation->blub);
		$this->assertEqual($objectWithoutCustomisation->blub, null);

		$testClass = new TestViewClassMethod(array("blub" => 2));
		$this->assertEqual($testClass->blub, 2);
		$this->assertEqual($testClass->myLittleValue, "val");
		$this->assertEqual($testClass->myLittleTest, "val");
		$this->assertEqual($testClass->myLittleValue(), "val");
		$this->assertIsA($testClass->myLittleTest(), "DBField");
		$this->assertEqual($testClass->__call("myLittleValue", array()), "val");
		$this->assertTrue(gObject::method_exists($testClass,"myLittleValue"));
		$this->assertTrue(gObject::method_exists($testClass,"myLittleTest"));
		$this->assertFalse(gObject::method_exists($testClass->classname,"myLittleTest"));
		$this->assertEqual($testClass->getMyLittleTest, "val");
	}

    public function testGetCast() {
        $reflectionMethodGetCast = new ReflectionMethod('ViewAccessableData', 'getCast');
        $reflectionMethodGetCast->setAccessible(true);

        $view = new TestViewClassMethod();

        $this->assertEqual($view->getCast("test"), "Switch");
        $this->assertEqual($view->getCast("TEST"), "Switch");
        $this->assertEqual($view->getCast(" teSt "), "Switch");
    }


    /**
     * tests extended property
     */
    public function testExtension() {

        $view = new TestViewClassMethod();

        $this->assertTrue(gObject::method_exists($view, "getproperty"));

        $prop = TestViewClassExtendedProperty::$prop = randomString(5);

        $this->assertEqual($view->property, $prop);
        $this->assertEqual($view->PROPERTY, $prop);
        $this->assertEqual($view->getTemplateVar("property"), $prop);
    }

	/**
	 *
	 */
	public function testCreateNew() {
		$view = new ViewAccessableData();
		$this->assertEqual($view->createNew(array(123)), new ViewAccessableData(array(123)));
		$this->assertEqual($view->createNew(array(
			"class_name" => "VMTestGeneration"
		)), new VMTestGeneration(array(
			"class_name" => "VMTestGeneration"
		)));
		$this->assertEqual($view->createNew(array(
			"classname" => "VMTestGeneration"
		)), new ViewAccessableData(array(
			"classname" => "VMTestGeneration"
		)));
	}

    /**
     * checks if isOffsetMethod returns false correctly for getActions.
     *
     * 1. Create ViewAccessableData $view
     * 2. Assert that method_exists($view, "getActions") returns true
     * 3. Assert that $view->isOffsetMethod("getactions") is false
     */
	public function testIsOffsetMethod() {
        $view = new ViewAccessableData();

        $this->assertTrue(method_exists($view, "getActions"));

        $reflectionMethod = new ReflectionMethod(ViewAccessableData::class, "isOffsetMethod");
        $reflectionMethod->setAccessible(true);
        $this->assertFalse($reflectionMethod->invoke($view, "getActions"));
    }

    /**
     * tests if getter are XSS-Protected.
     *
     * 1. Create TestViewClassMethod $test
     * 2. Assert that $test->getTemplateVar("MyLittleXSSValue") is equal to htmlentities("<xss>")
     */
    public function testGetterXSSProtection() {
        $test = new TestViewClassMethod();
        $this->assertEqual(htmlentities("<xss>"), $test->getTemplateVar("MyLittleXSSValue"));
    }

    /**
     * tests if getter are only xss-protected in templates
     *
     * 1. Create TestViewClassMethod $test
     * 2. Assert that $test->MyLittleXSSValue is equal to "<xss>"
     */
    public function testGetterXSSProtectionOnlyTemplate() {
        $test = new TestViewClassMethod();
        $this->assertEqual("<xss>", $test->MyLittleXSSValue);
    }
}

class VMTestGeneration {
	public $data;

	public function __construct($data = array())
	{
		$this->data = $data;
	}
}

class TestClassWithForTemplate {
	public $templateInfo;

	public function getTemplateVar($remaining) {
		return $remaining;
	}

	public function forTemplate() {
		return $this->templateInfo;
	}
}

class TestClassWithForTemplateToString {
	public $templateInfo;

	public function __toString() {
		return $this->templateInfo;
	}
}

class TestViewClassMethod extends ViewAccessableData {

    static $casting = array(
        "test" => "Switch"
    );

	public function myLittleValue() {
		return "val";
	}

	public function getMyLittleTest() {
		return "val";
	}

	public function myLittleXSSValue() {
	    return "<xss>";
    }
}

class TestViewClassExtendedProperty extends Extension {

    static $prop = "";

    protected static $extra_methods = array(
        "getProperty"
    );

    public function getProperty() {
        return self::$prop;
    }
}

gObject::extend("TestViewClassMethod", "TestViewClassExtendedProperty");
