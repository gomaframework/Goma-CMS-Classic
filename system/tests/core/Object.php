<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Object.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ObjectTest extends GomaUnitTest implements TestAble {
	
	static $area = "framework";
	/**
	 * name
	*/
	public $name = "Object";

	/**
	 * setup test
	*/
	public function setUp() {
		$this->o = new TestObject();
		$this->dummyMethod = new DummyMethodTest();
	}

	public function tearDown() {
		unset($this->o);
	}
	
	/**
	 * tests basic functionallity
	*/	
	public function testBasic() {
		$this->assertEqual($this->o->action1(), 1);
	}
	
	/**
	 * tests basic functionallity
	*/	
	public function testExtension() {
		$this->assertEqual($this->o->extra_method(), "it works");
	}
	
	/**
	 * tests linkMethod
	*/
	public function testLinkMethod() {
		Object::linkMethod("TestObject", "testlink", "testObjectExtFunction", true);
		$this->assertEqual($this->o->testlink(), "test");
		
		Object::linkMethod("TestObject", "testlink_absolute", "testObjectExtFunction", false);
		$this->assertEqual($this->o->testlink_absolute(), "test");
	}
	
	/**
	 * tests createMethod
	*/
	public function testCreateMethod() {
		Object::createMethod("TestObject", "testcreate", "return 'blah';", true);
		$this->assertEqual($this->o->testcreate(), "blah");
		
		Object::createMethod("TestObject", "testcreate_absolute", "return 'blub';", false);
		$this->assertEqual($this->o->testcreate_absolute(), "blub");
	}
	
	/**
	 * extensions
	*/
	public function testGetExtensions() {
		$this->assertEqual($this->o->getExtensions(), array("testobjectextension"));
	}
	
	/**
	 * extension-instance
	*/
	public function testGetInstance() {
		$this->assertIsA($this->o->getInstance("testobjectextension"), "testobjectextension");
	}
	
	/**
	 * extending calling
	*/
	public function testcallExtending() {
		$this->assertEqual($this->o->callExtending("callExtends"), array("works"));
	}

	public function testDummyMethodTest() {
		$this->assertEqual($this->dummyMethod->ownMethod(), "blah");
		$this->assertEqual($this->dummyMethod->__call("ownMethod", array()), "blah");
		$this->assertEqual($this->dummyMethod->__call("OWNMETHOD", array()), "blah");
		$this->assertEqual($this->dummyMethod->__call("myDynamicMethod", array()), "It works");
		$this->assertEqual($this->dummyMethod->myDynamicMethod(), "It works");

		$this->assertFalse(Object::method_exists($this->dummyMethod->classname, "myDynamicMethod"));
	}

    public function testEmptyMethod() {
        $this->assertFalse(Object::method_exists("", ""));
        $this->assertFalse(Object::method_exists("test", ""));
        $this->assertFalse(Object::method_exists("", "test"));
    }


    public function testInstance() {
        $this->assertIsA(Object::instance("DummyMethodTest"), "DummyMethodTest");
        $this->assertClone(Object::instance("DummyMethodTest"), Object::instance("DummyMethodTest"));

        // check for cloning
        $o = Object::instance("DummyMethodTest");
        $o->b = 1;
        $this->assertEqual(Object::instance($o)->b, 1);
        $this->assertNotEqual(Object::instance("DummyMethodTest")->b, 1);

        // check if these are clones
        $second = Object::instance("DummyMethodTest");
        $second->b = 2;
        $this->assertEqual(Object::instance($o)->b, 1);
        $this->assertNotEqual(Object::instance("DummyMethodTest")->b, 1);
        $this->assertEqual(Object::instance($second)->b, 2);
        $this->assertNotEqual(Object::instance("DummyMethodTest")->b, 2);
    }

    public function testStaticVars()
    {
        $this->assertEqual(Object::getStatic("StaticTestClass", "b"), StaticTestClass::$b);

        $r = randomString(2);
        Object::setStatic("StaticTestClass", "b", $r);
        $this->assertTrue(Object::hasStatic("StaticTestClass", "b"));
        $this->assertFalse(Object::hasStatic("StaticTestClass", "c"));
        $this->assertEqual(Object::getStatic("StaticTestClass", "b"), $r);
        $this->assertEqual(Object::getStatic("StaticTestClass", "b"), StaticTestClass::$b);
    }

    public function testSetStaticVarExceptions()
    {
        try {
            Object::setStatic("StaticTestClass", "c", 3);

            $this->fail("Variable not exists, but setStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "LogicException");
        }

        try {
            Object::setStatic("StaticTestClassNotExisting", "c", 3);

            $this->fail("Class not exists, but setStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "LogicException");
            $this->assertPattern("/Class/i", $e->getMessage());
        }

        try {
            Object::setStatic("StaticTestClass", "", 3);

            $this->fail("Variable empty, but setStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "LogicException");
            $this->assertPattern("/variable/i", $e->getMessage());
        }
    }

    public function testHasStaticVarExceptions()
    {
        try {
            Object::hasStatic("StaticTestClassNotExisting", "c");

            $this->fail("Class not exists, but setStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "LogicException");
            $this->assertPattern("/Class/i", $e->getMessage());
        }

        try {
            Object::hasStatic("StaticTestClass", "");

            $this->fail("Variable empty, but setStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "LogicException");
            $this->assertPattern("/variable/i", $e->getMessage());
        }
    }

    public function testStaticCalls() {
        $this->assertEqual(Object::callStatic("StaticTestClass", "call"), StaticTestClass::call());

        try {
            Object::callStatic("StaticTestClass", "prot");

            $this->fail("Method protected, but callStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "BadMethodCallException");
        }

        try {
            Object::callStatic("StaticTestClassNotExisting", "prot");
            $this->fail("Class not exists, but callStatic does not fire an Exception.");

        } catch(Exception $e) {
            $this->assertIsA($e, "LogicException");
            $this->assertPattern("/Class/i", $e->getMessage());
        }
    }
}

class DummyMethodTest extends Object {
    public $b;

	public function ownMethod() {
		return "blah";
	}

	public function __cancall($method) {
		if($method == "myDynamicMethod") {
			return true;
		}

		return false;
	}

	public function __call($method, $args) {
		if($method == "myDynamicMethod") {
			return "It works";
		}

		return parent::__call($method, $args);
	}

}

class TestObject extends Object {
	
	
	
	public function action1() {
		return 1;
	}
}

class TestObjectExtension extends Extension {
	static $extra_methods = array(
		"extra_method"
	);
	
	public static function extra_method() {
		return "it works";
	}
	public function callExtends() {
		return "works";
	}
}
Object::extend("testObject", "TestObjectExtension");

function testObjectExtFunction() {
	return "test";
}

class StaticTestClass {
    public static $b = "a";
    public static function call()
    {
        return 1;
    }

    protected static function prot()
    {
        return 2;
    }
}