<?php
namespace Goma\Test\Director;

use Goma\Test\Controller\ControllerTestENV\TestControllerForRequestController;
use Goma\Test\Controller\ControllerTestENV\TestSubControllerForRequestController;
use Goma\Test\Controller\ControllerTestENV\TestSubControllerWithSubForRequestController;

defined("IN_GOMA") OR die();
/**
 *  Unit-Tests for Director-Class.
 *
 * @package		Goma\Test\Director
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DirectorTest extends \GomaUnitTest {
    /**
     *
     */
    public function testControllerENVFromControllerStandard() {
        $sortedRules = $this->getSortedRules();
        try {
            $this->setSortedRules(array(array(
                "blub" => TestControllerForRequestController::class,
                "sub" => TestSubControllerForRequestController::class,
                "subsub" => TestSubControllerWithSubForRequestController::class
            )));

            $request = new \Request("get", "blub");
            $this->assertEqual("Sub", \Director::directRequest($request, false));
        } finally {
            $this->setSortedRules($sortedRules);
        }
    }

    /**
     *
     */
    public function testControllerENVFromControllerWithReHierarchy() {
        $sortedRules = $this->getSortedRules();
        try {
            $this->setSortedRules(array(array(
                "blub" => TestControllerForRequestController::class,
                "sub" => TestSubControllerForRequestController::class,
                "subsub" => TestSubControllerWithSubForRequestController::class
            )));

            $request = new \Request("get", "blub/test");
            $this->assertEqual("Sub", \Director::directRequest($request, false));
        } finally {
            $this->setSortedRules($sortedRules);
        }
    }

    /**
     *
     */
    public function testControllerENVFromControllerWithSubHierarchy() {
        $sortedRules = $this->getSortedRules();
        try {
            $this->setSortedRules(array(array(
                "blub" => TestControllerForRequestController::class,
                "sub" => TestSubControllerForRequestController::class,
                "subsub" => TestSubControllerWithSubForRequestController::class
            )));

            $request = new \Request("post", "blub/test");
            $this->assertEqual("SubSub", \Director::directRequest($request, false));
        } finally {
            $this->setSortedRules($sortedRules);
        }
    }

    public function testServe() {
        $sortedRules = $this->getSortedRules();
        try {
            $this->setSortedRules(array(array(
                "blub" => TestControllerForRequestController::class,
                "sub" => TestSubControllerForRequestController::class,
                "subsub" => TestSubControllerWithSubForRequestController::class,
                "serve" => ServingController::class
            )));

            $request = new \Request("post", "serve/blub/test");
            $response = \Director::directRequest($request, false);
            $this->assertEqual("Test: SubSub", $response);
        } finally {
            $this->setSortedRules($sortedRules);
        }
    }

    public function testDirectRequestAffectsRequest() {
        $sortedRules = $this->getSortedRules();
        try {
            $this->setSortedRules(array(array(
                                            "blub" => TestControllerForRequestController::class,
                                            "sub" => TestSubControllerForRequestController::class,
                                            "subsub" => TestSubControllerWithSubForRequestController::class,
                                            "serve" => ServingController::class
                                        )));

            $request = new \Request("post", "serve/blub/test");
            $request2 = clone $request;
            \Director::directRequest($request, false);
            $this->assertEqual($request, $request2);
        } finally {
            $this->setSortedRules($sortedRules);
        }
    }

    public function testDirectRequestThrows404() {
        $sortedRules = $this->getSortedRules();
        try {
            $this->setSortedRules(array());

            $request = new \Request("post", "serve/blub/test");

            $this->assertThrows(function() use($request) {
                \Director::directRequest($request, false);
            }, "DataNotFoundException");
        } finally {
            $this->setSortedRules($sortedRules);
        }
    }

    protected function setSortedRules($value) {
        $sortedRules = new \ReflectionProperty("Director", "sortedRules");
        $sortedRules->setAccessible(true);
        $sortedRules->setValue(null, $value);
    }

    protected function getSortedRules() {
        return \Director::getSortedRules();
    }
}

class ServingController extends \RequestHandler {
    public function index() {
        $controller = new TestControllerForRequestController();
        return $controller->handleRequest($this->request, true);
    }

    public function serve($content, $body)
    {
        return parent::serve("Test: " . $content, $body);
    }
}