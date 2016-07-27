<?php defined("IN_GOMA") OR die();
/**
 * Base-Class for all Goma Unit-Tests.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

require_once(ROOT . "/system/libs/thirdparty/simpletest/unit_tester.php");

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

	public function assertPattern() {
		call_user_func_array(array($this, "assertRegExp"), func_get_args());
	}

	public function assertNoPattern($pattern, $str, $info) {
		$this->assertFalse(preg_match($pattern, $str), $info);
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
