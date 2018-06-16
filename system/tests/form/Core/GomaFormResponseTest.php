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

    /**
     * checks if IsfullPage is false for strings.
     */
    public function testIsStringNotFullPage() {
        $mock = new FormMock();
        $mock->response = "lalala";
        $mock->request = new Request("get", "test");

        $response = new GomaFormResponse(null, $mock);
        $this->assertFalse($response->isFullPage());
    }

    /**
     * checks if IsfullPage is false for strings.
     */
    public function testIsArrayNotFullPage() {
        $mock = new FormMock();
        $mock->response = array("lalala");
        $mock->request = new Request("get", "test");

        $response = new GomaFormResponse(null, $mock);
        $this->assertFalse($response->isFullPage());
    }

    /**
     * checks if IsfullPage is false for strings.
     */
    public function testIsResponseFullPageCalled() {
        $mock = new FormMock();

        $body = new \GomaResponseBody();
        $body->setIsFullPage(false);
        $mock->response = new \GomaResponse(null, $body);

        $mock->request = new Request("get", "test");

        $response = new GomaFormResponse(null, $mock);
        $this->assertFalse($response->isFullPage());
    }

    /**
     * checks if IsfullPage is false for strings.
     */
    public function testIsResponseFullPageCalledTrue() {
        $mock = new FormMock();

        $body = new \GomaResponseBody();
        $body->setIsFullPage(true);
        $mock->response = new \GomaResponse(null, $body);

        $mock->request = new Request("get", "test");

        $response = new GomaFormResponse(null, $mock);
        $this->assertTrue($response->isFullPage());
    }

    /**
     * tests if raw body is not changed by render functions.
     */
    public function testRawBodyNotChangedByRenderFunction() {
        $form = new \Form(new \Controller(), "abc");
        $body = GomaFormResponse::create(null, $form);
        $body->addRenderFunction(function($content, $formResponse){
            return '<div class="wrapper">' . $content . '</div>';
        });
        $body->setBody(true);

        $this->assertIdentical(true, $body->getRawBody());
        $this->assertEqual('<div class="wrapper">1</div>', $body->getBody());
        $this->assertIdentical(true, $body->getRawBody());
    }
}

class FormMock extends \gObject {
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
    public function submitOrRenderForm() {
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
