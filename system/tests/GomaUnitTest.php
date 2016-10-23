<?php defined("IN_GOMA") OR die();

/**
 * Base-Class for all Goma Unit-Tests.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @method void assertNull($actual, $message = null)
 * @method void assertTrue($actual, $message = null)
 * @method void assertFalse($actual, $message = null)
 * @method void assertRegExp($regexp, $actual, $message = null)
 * @method void assertLessThanOrEqual($expected, $actual, $message = null)
 * @method void assertGreaterThanOrEqual($expected, $actual, $message = null)
 * @method void assertInstanceOf($class, $object, $message = null)
 * @method void assertNotInstanceOf($class, $object, $message = null)
 */
abstract class GomaUnitTest extends PHPUnit_Framework_TestCase implements TestAble {
	/**
	 * information about area.
	*/
	static $area = "default";

	/**
	 * name of test.
	*/
	public $name = null;

	public function __construct() {
		if($this->name) {
			parent::__construct($this->name);
		}

		parent::__construct();
	}

    public function assertThrows($callback, $exceptionName) {
        try {
            call_user_func_array($callback, array());

            $this->assertFalse(true, "Expected Exception $exceptionName, but no Exception were thrown.");
        } catch(Exception $e) {
			if(!is_a($e, $exceptionName)) {
				$this->assertFalse(true, $e->getMessage());
			}
            $this->assertIsA($e, $exceptionName);
        }
    }

	public function assertEqual() {
		call_user_func_array(array($this, "assertEquals"), func_get_args());
	}
	public function assertNotEqual() {
		call_user_func_array(array($this, "assertNotEquals"), func_get_args());
	}

	public function assertIdentical() {
		call_user_func_array(array($this, "assertSame"), func_get_args());
	}

	public function assertNotIdentical() {
		call_user_func_array(array($this, "assertNotSame"), func_get_args());
	}

	public function assertPattern() {
		call_user_func_array(array($this, "assertRegExp"), func_get_args());
	}

	public function assertNoPattern($pattern, $str, $msg = null) {
		$this->assertFalse(!!preg_match($pattern, $str), $msg);
	}

	public function assertWithinMargin($info, $expected, $margin, $msg = null) {
		$this->assertLessThanOrEqual($expected + $margin, $info, $msg);
		$this->assertGreaterThanOrEqual($expected - $margin, $info, $msg);
	}

	public function assertNotA($obj, $class, $msg = null) {
		if($class == "array") {
			$this->assertFalse(is_array($obj));
		} else if($class == "string") {
			$this->assertFalse(is_string($obj));
		} else {
			call_user_func_array(array($this, "assertNotInstanceOf"), array(
				$class, $obj, $msg
			));
		}
	}

	public function assertIsA($obj, $class, $msg = null) {
		if($class == "array") {
			$this->assertTrue(is_array($obj));
		} else if($class == "string") {
			$this->assertTrue(is_string($obj));
		} else {
			call_user_func_array(array($this, "assertInstanceOf"), array(
				$class, $obj, $msg
			));
		}
	}
}
