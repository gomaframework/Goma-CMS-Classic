<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for RequestHandler-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */


class RequestHandlerTest extends GomaUnitTest {

	public function testPermissionSystemBasic()
    {
        $h = new TestableRequestHandler();
        $h->Init(new Request("get", ""));
        $this->assertTrue($h->hasAction("testAction", $h->classname));
        $this->assertEqual($h->handleAction("testAction"), $h->content);
    }

    /**
     * tests if hasAction returns true if method returns true and calls allowed_action method.
     */
    public function testPermissionSystemMethodHasActionTrue()
    {
        $h = new TestableRequestHandler();
        $h->Init(new Request("get", ""));
        $this->assertFalse($h->wasCalled);
        $this->assertTrue($h->hasAction("testActionMethod", $h->classname));
        $this->assertTrue($h->wasCalled);
        $this->assertEqual($h->handleAction("testActionMethod"), $h->content);
    }

    /**
     * tests if hasAction returns false if method returns false and calls allowed_action method.
     */
    public function testPermissionSystemMethodHasActionFalse()
    {
        $h = new TestableRequestHandler();
        $h->Init(new Request("get", ""));
        $h->shouldCall = false;
        $this->assertFalse($h->wasCalled);
        $this->assertFalse($h->hasAction("testActionMethod"));
        $this->assertTrue($h->wasCalled);
    }


    /**
     * tests if hasAction returns false if method returns false and calls allowed_action method.
     * $classWithActionDefined = TestableRequestHandler
     */
    public function testPermissionSystemMethodHasActionFalseSameClass()
    {
        $h = new TestableRequestHandler();
        $h->Init(new Request("get", ""));
        $h->shouldCall = false;
        $this->assertFalse($h->wasCalled);
        $this->assertFalse($h->hasAction("testActionMethod", TestableRequestHandler::class));
        $this->assertTrue($h->wasCalled);
    }

    /**
     * tests if hasAction returns true if method returns true and calls allowed_action method.
     * $classWithActionDefined = TestableRequestHandler
     */
    public function testPermissionSystemMethodHasActionTrueSameClass()
    {
        $h = new TestableRequestHandler();
        $h->Init(new Request("get", ""));
        $h->shouldCall = true;
        $this->assertFalse($h->wasCalled);
        $this->assertTrue($h->hasAction("testActionMethod", TestableRequestHandler::class));
        $this->assertTrue($h->wasCalled);
    }

    /**
     * tests if handleRequest does not handle action if allowed action method returns false
     * @throws Exception
     */
    public function testPermissionSystemMethodHandleRequestFalse()
    {
        $h = new TestableRequestHandler();
        $h->shouldCall = false;
        $h->content = "test";

        $request = new Request("get", "testActionMethod");
        $this->assertEqual("index", $h->handleRequest($request));
    }

    /**
     * tests if handleRequest does handle action if allowed action method returns true
     * @throws Exception
     */
    public function testPermissionSystemMethodHandleRequestTrue()
    {
        $h = new TestableRequestHandler();
        $h->shouldCall = true;
        $h->content = "test";

        $request = new Request("get", "testActionMethod");
        $this->assertEqual($h->content, $h->handleRequest($request));
    }

    public function testPermissionSystemMethodFalse()
    {
        $h = new TestableRequestHandler();
        $h->Init(new Request("get", ""));
        $h->shouldCall = false;
        $this->assertFalse($h->wasCalled);
        $this->assertFalse($h->hasAction("testActionMethod"));
        $this->assertTrue($h->wasCalled);
    }

    public function testHandleAction() {
        $h = new TestableRequestHandler();
        $h->Init(new Request("get", ""));
		// you should be able to call a method also when hasAction returns false, if method exists.
		$this->assertEqual($h->handleAction("testActionDisallowed"), "dis");
	}

	public function testServe() {
        $h = new TestableRequestHandler();
        $h->Init(new Request("get", ""));
        $h->content = "blub";
        // serve should not be called when handleAction gets called or handleRequest.
        $this->assertEqual($h->serve($h->handleAction("testAction")), $h->content . 1);
    }

	public function testRequestSystemExtractsParams() {
		$h = new TestableRequestHandler();
		$r = new Request("GET", "testAction/1");

		$this->assertEqual($h->handleRequest($r), $h->content);
		$this->assertEqual($h->getParam("id"), 1);
		$this->assertEqual($h->getParam("uid"), null);
	}

    /**
     * checks if new rule-action system was implemented by developer.
     */
	public function testAllowedActionWithoutRule() {
	    $problemsInClassesWithActions = array();
	    foreach(ClassInfo::getChildren(RequestHandler::class) as $class) {
	        $actions = (array) call_user_func_array(array($class, "getExtendedAllowedActions"), array());
	        $rules = StaticsManager::getInheritedStaticArrayFromMethod($class, "getExtendedUrlHandlers");

	        foreach($actions as $action => $info) {
	            if(RegexpUtil::isNumber($action)) {
                    if(!in_array($info, $rules)) {
                        $problemsInClassesWithActions[] = $class . "::" . $info;
                    }
                } else {
                    // allow rules in format "action" => false without url-handler
                    if(!in_array($action, $rules) && $info !== false) {
                        $problemsInClassesWithActions[] = $class . "::" . $action;
                    }
                }
            }
        }

        $this->assertEqual(array(
            strtolower("TestableRequestHandler::testActionAllowed"),
            strtolower("TestableRequestHandler::testActionMethodWithoutURl")
        ), $problemsInClassesWithActions);
    }
}

class TestableRequestHandler extends RequestHandler {

	public $shouldCall = true;
	public $content = "test";
	public $wasCalled = false;

	static $url_handlers = array(
		'testAction/$Id'       => 'testAction',
        'testActionMethod/$Id' => 'testActionMethod',
	);

	static $allowed_actions = array(
		"testAction" => true,
        "testActionDisallowed" => false,
        "testActionMethod" => "->canCallTestMethod",
        "testActionAllowed" => true,
        "testActionMethodWithoutURl" => "->canCallTestMethod"
	);

	public function index()
    {
        return "index";
    }

    public function serve($content, $body) {
		return $content . 1;
	}

	public function canCallTestMethod() {
		$this->wasCalled = true;
		return $this->shouldCall;
	}

	public function testAction() {
		return $this->content;
	}

	public function testActionMethod() {
	    return $this->content;
    }

    public function testActionDisallowed() {
        return "dis";
    }
}