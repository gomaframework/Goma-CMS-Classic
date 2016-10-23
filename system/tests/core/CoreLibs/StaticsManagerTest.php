<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for StaticsManager.
 *
 * @package		Goma\Core
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class StaticsManagerTest extends GomaUnitTest implements TestAble
{

    static $area = "framework";
    /**
     * name
     */
    public $name = "StaticsManager";


    public function testStaticVars()
    {
        $this->assertEqual(StaticsManager::getStatic("StaticTestClass", "b"), StaticTestClass::$b);

        $r = randomString(2);
        StaticsManager::setStatic("StaticTestClass", "b", $r);
        $this->assertTrue(StaticsManager::hasStatic("StaticTestClass", "b"));
        $this->assertFalse(StaticsManager::hasStatic("StaticTestClass", "c"));
        $this->assertEqual(StaticsManager::getStatic("StaticTestClass", "b"), $r);
        $this->assertEqual(StaticsManager::getStatic("StaticTestClass", "b"), StaticTestClass::$b);
    }

    public function testSetStaticVarExceptions()
    {
        try {
            StaticsManager::setStatic("StaticTestClass", "c", 3);

            $this->fail("Variable not exists, but setStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "ReflectionException");
        }

        try {
            StaticsManager::setStatic("StaticTestClassNotExisting", "c", 3);

            $this->fail("Class not exists, but setStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "ReflectionException");
            $this->assertPattern("/Class/i", $e->getMessage());
        }

        try {
            StaticsManager::setStatic("StaticTestClass", "", 3);

            $this->fail("Variable empty, but setStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "ReflectionException");
            $this->assertPattern("/variable/i", $e->getMessage());
        }
    }

    /**
     * tests if hasStatic is throwing InvalidArgument for empty variable.
     */
    public function testHasStaticWithEmptyVariableException() {
        try {
            StaticsManager::hasStatic("StaticTestClass", "");

            $this->fail("Variable empty, but setStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "InvalidArgumentException");
            $this->assertPattern("/variable/i", $e->getMessage());
        }
    }

    /**
     * tests if hasStatic is throwing Exception for unknown class.
     */
    public function testHasStaticVarExceptionUnknownClass()
    {
        try {
            StaticsManager::hasStatic("StaticTestClassNotExisting", "c");

            $this->fail("Class not exists, but setStatic does not fire an Exception.");
        } catch (Exception $e) {
            $this->assertIsA($e, "ReflectionException");
            $this->assertPattern("/Class/i", $e->getMessage());
        }
    }

    /**
     *  @testdox tests that it correctly works for protected statics.
     */
    public function testHasStaticIsNotThrowingExceptionForPrivateStatic() {
        $this->assertTrue(StaticsManager::hasStatic("testDefineStatics", "privateStatic"));
    }

    /**
     * @testdox tests if public invocation works.
     */
    public function testPublicStaticCallIsSameAsNormal() {
        $this->assertEqual(StaticsManager::callStatic("StaticTestClass", "call"), StaticTestClass::call());
    }

    /**
     * @testdox tests if public invocation works.
     */
    public function testPublicStaticCallIsSameAsNormalWithoutPublish() {
        $this->assertEqual(StaticsManager::callStatic("StaticTestClass", "callWithoutPublic"), StaticTestClass::callWithoutPublic());
    }

    /**
     * tests if protected methods throws exception.
     */
    public function testStaticCallIsThrowingExceptionForProtected() {
        try {
            StaticsManager::callStatic("StaticTestClass", "prot");

            $this->fail("Method protected, but callStatic does not fire an Exception.");
        } catch(Exception $e) {
            $this->assertIsA($e, "BadMethodCallException");
        }
    }

    /**
     * tests if logicException is thrown for unknown class.
     */
    public function testStaticCallIsThrowingDifferentForUnknownClass() {
        try {
            StaticsManager::callStatic("StaticTestClassNotExisting", "prot");
            $this->fail("Class not exists, but callStatic does not fire an Exception.");

        } catch(Exception $e) {
            $this->assertIsA($e, "LogicException");
            $this->assertPattern("/Class/i", $e->getMessage());
        }
    }

    /**
     * tests that protected method call works when invoking with true.
     */
    public function testStaticCallIsNotThrowingExceptionForProtectedWithTrue() {
            $this->assertEqual(2, StaticsManager::callStatic("StaticTestClass", "prot", true));
    }

    public function testDefineStatics() {

        $this->assertFalse(testDefineStatics::$hasBeenCalled);
        $t = new testDefineStatics();
        $this->assertTrue($t->hasBeenLocallyCalled);
        $this->assertTrue(testDefineStatics::$hasBeenCalled);

        $b = new testDefineStatics();
        $this->assertTrue(testDefineStatics::$hasBeenCalled);
        $this->assertEqual($b->hasBeenLocallyCalled, defined('GENERATE_CLASS_INFO'));
    }
}

class testDefineStatics extends gObject {
    public static $hasBeenCalled = false;
    public $hasBeenLocallyCalled = false;

    private static $privateStatic = 2;

    public function defineStatics() {
        self::$hasBeenCalled = true;
        $this->hasBeenLocallyCalled = true;
    }
}

class StaticTestClass {
    public static $b = "a";
    public static function call()
    {
        return 1;
    }

    static function callWithoutPublic()
    {
        return 4;
    }

    protected static function prot()
    {
        return 2;
    }
}