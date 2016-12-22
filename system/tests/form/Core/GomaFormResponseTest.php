<?php
namespace Goma\Test;
use GomaFormResponse;
use GomaUnitTest;
use Request;

defined("IN_GOMA") OR die();

/**
 * Tests GomaFormResponse Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class GomaFormResponseTest extends GomaUnitTest {
    /**
     *
     */
    public function testRenderString() {
        $mock = new FormMock();
        $mock->response = "test";
        $mock->request = new Request("get", "test");
        $gomaFormResponse = new GomaFormResponse(null, $mock);
        $gomaFormResponse->setBody(
            $gomaFormResponse->getBody()->setParseHTML(false)
        );
        $this->assertEqual($gomaFormResponse->render(), $mock->response);
    }

    /**
     *
     */
    public function testRenderArray() {
        $mock = new FormMock();
        $mock->response = array("test");
        $mock->request = new Request("get", "test");
        $gomaFormResponse = new GomaFormResponse(null, $mock);
        $gomaFormResponse->setBody(
            $gomaFormResponse->getBody()->setParseHTML(false)
        );
        $this->assertEqual($gomaFormResponse->render(), print_r($mock->response, true));
    }
}

class FormMock {
    /**
     * @var mixed
     */
    public $response;

    /**
     * @var Request
     */
    public $request;

    /**
     * @return mixed
     */
    public function renderData() {
        return $this->response;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
