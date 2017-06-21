<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for FormRequestExtension-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class FormRequestExtensionTests extends GomaUnitTest
{
    /**
     * area
     */
    static $area = "Form";

    /**
     * internal name.
     */
    public $name = "FormRequestExtension";

    /**
     * tests forms with name form.
     */
    public function testFormWithNameForm() {
        $request = new Request("get", "forms/form/form/blah/test");
        $this->assertEqual($this->unitTestRequests(
            array('$Action/$Id', '$Action', '$Id', '$blah', '$hulapalu/$lalala/$lustigeNamen', '$blah//$blub/$blib'), $request), array("form", "blah"));
    }

    /**
     * tests after forms/form three more parts
     */
    public function testFormsFormWithThreeMoreParts() {
        $request = new Request("get", "forms/form/blah/test/test");
        $this->assertEqual($this->unitTestRequests(
            array('$Action/$Id', '$Action', '$Id', '$blah', '$hulapalu/$lalala/$lustigeNamen', '$blah//$blub/$blib'), $request), array("blah", "test"));
    }

    /**
     * tests after forms/form two more parts
     */
    public function testFormsFormWithTwoMoreParts() {
        $request = new Request("get", "forms/form/blah/test");
        $this->assertEqual($this->unitTestRequests(
            array('$Action/$Id', '$Action', '$Id', '$blah', '$hulapalu/$lalala/$lustigeNamen', '$blah//$blub/$blib'), $request), array("blah", "test"));
    }

    /**
     * tests if with any url-handler condition a URL with form/form does not work.
     */
    public function testRequestFormFormNotWorking() {
        $request = new Request("get", "form/form/blah/test");
        $this->assertEqual($this->unitTestRequests(
            array('$Action/$Id', '$Action', '$Id', '$blah', '$hulapalu/$lalala/$lustigeNamen', '$blah//$blub/$blib'), $request), "");
    }

    /**
     * 1. foreach urlScheme in matchUrls
     * 1.1 assert that it matches request
     * 1.2 Creates FormRequestExtension with MockControlller
     * 1.3 Executres onBeforeHandleAction on FormRequestExtension
     * 1.4 Adds Result to array, which is either "" or array(form, field)
     * 2. Asserts that all array results are the same
     * 3. returns first array result
     *
     * @param string $matchUrlSchemes
     * @param Request $request
     * @return mixed
     */
    public function unitTestRequests($matchUrlSchemes, $request) {
        $contents = array();
        foreach((array) $matchUrlSchemes as $currentMatch) {
            $currentRequest = clone $request;

            $this->assertTrue(!!$currentRequest->match($currentMatch, true));
            $controller = new RequestHandler();
            $controller->Init($currentRequest);

            $extension = new FormRequestExtension(new MockControllerForExternalForm());

            $extension->setOwner($controller);
            $extension->onBeforeHandleAction("", $content, $handleWithAction);
            $contents[] = $content;
        }

        if(count($contents) > 1) {
            for($i = 0; $i < count($contents); $i++) {
                $this->assertEqual($contents[0], $contents[$i]);
            }
        }

        return $contents[0];
    }
}

class MockControllerForExternalForm extends RequestHandler {
    /**
     * @param Request $request
     * @param bool $subController
     * @return false|null|string
     * @throws Exception
     */
    public function handleRequest($request, $subController = false)
    {
        $form = $request->getParam("form");
        $field = $request->getParam("field");
        return array($form, $field);
    }
}
