<?php
namespace Goma\Test;
defined("IN_GOMA") OR die();

/**
 * Tests Request-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class RequestTest extends \GomaUnitTest {
    /**
     * tests method
     */
    public function testGet() {
        $request = new \Request("get", "blub");
        $this->assertTrue($request->isGET());
        $this->assertFalse($request->isPOST());
    }

    /**
     * tests method
     */
    public function testIsDefaultNotAjax() {
        $request = new \Request("get", "blub");
        $this->assertFalse($request->is_ajax());
    }

    /**
     * tests method
     */
    public function testIsAjaxGetParam() {
        $request = new \Request("get", "blub", array("ajaxfy" => true));
        $this->assertTrue($request->is_ajax());
    }

    /**
     * tests method
     */
    public function testIsAjaxPostParam() {
        $request = new \Request("get", "blub", array(), array("ajaxfy" => true));
        $this->assertTrue($request->is_ajax());
    }
}
