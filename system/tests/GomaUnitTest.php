<?php defined("IN_GOMA") OR die();

require_once(ROOT . "/system/libs/thirdparty/simpletest/unit_tester.php");

/**
 * Base-Class for all Goma Unit-Tests.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @method void assertIsNull($response, $message = null)
 * @method void assertTrue($response, $message = null)
 * @method void assertFalse($response, $message = null)
 * @method void assertRegExp($response, $regexp, $message = null)
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
		$this->assertLessThanOrEqual($info, $expected + $margin, $msg);
		$this->assertGreaterThanOrEqual($info, $expected - $margin, $msg);
	}

	public function assertNotA($obj, $class, $msg = null) {
		call_user_func_array(array($this, "assertNotInstanceOf"), array(
			$class, $obj, $msg
		));
	}

	public function assertIsA($obj, $class, $msg = null) {
		call_user_func_array(array($this, "assertInstanceOf"), array(
			$class, $obj, $msg
		));
	}
}
