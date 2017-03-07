<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for contentController.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class contentControllerTest extends GomaUnitTest
{
    static $area = "cms";
    /**
     * name
     */
    public $name = "contentController";

    /**
     * tests checkForReadPermission
     */
    public function testcheckForReadPermission() {
        $this->assertIdentical($this->unitTestCheckForReadPermission("all", null, false), true);
        $this->assertIdentical($this->unitTestCheckForReadPermission("all", "blub", true), true);

        // this method should ONLY check for Password
        $this->assertIdentical($this->unitTestCheckForReadPermission("admin", null, false), true);
        $this->assertIdentical($this->unitTestCheckForReadPermission("admin", "blah", true), true);

        $this->assertIdentical($this->unitTestCheckForReadPermission("password", "test", true), true);
        $this->assertIdentical($this->unitTestCheckForReadPermission("password", "test12345   ", true), true);
        $this->assertEqual($this->unitTestCheckForReadPermission("password", "test", false), array("test"));
        $this->assertEqual($this->unitTestCheckForReadPermission("password", "12345  ", false), array("12345  "));
    }

    public function unitTestCheckForReadPermission($readPermissionType, $password, $shouldBeInKeychain) {
        $page = new Page();
        $page->read_permission = new Permission(array(
            "type" => $readPermissionType,
            "password" => $password
        ));

        $controller = new ContentController();
        $controller->setModelInst($page);
        if($shouldBeInKeychain) {
            $controller->keychain()->add($password);
        } else {
            $controller->keychain()->remove($password);
        }

        return $controller->checkForReadPermission();
    }

    /**
     *
     */
    public function testPassword() {
        $request = new Request("get", "");
        $controller = new ContentController(null, $chain = new Keychain(false, null, null, "testKeychainContentController"));
        $controller->setModelInst($page = new Page(array("data" => "Hallo Welt")));
        $page->read_permission->password = "1234";
        $page->read_permission->type = "password";
        $chain->clear();

        $this->assertEqual($page->read_permission->type, "password");

        $response = $controller->handleRequest($request);
        $this->assertPattern("/prompt_text/", (string) $response);
        $this->assertNoPattern("/Hallo Welt/", (string) $response);

        $chain->clear();
        // check if secret works
        $request->post_params["prompt_text"] = "1234";
        $response = $controller->handleRequest($request);
        $this->assertPattern("/prompt_text/", (string) $response);
        $this->assertNoPattern("/Hallo Welt/", (string) $response);

        $formData = GlobalSessionManager::globalSession()->get("form_state_prompt_contentcontroller");
        $request->post_params["prompt_text"] = "12345";
        $request->post_params["secret_form_" . md5("prompt_contentcontroller")] = $formData["secret"];
        $request->post_params["form_submit_prompt_contentcontroller"] = 1;
        $request->post_params["save"] = "ok";

        $chain->clear();
        $this->assertPattern("/error/", (string) $controller->handleRequest($request));

        $formData = GlobalSessionManager::globalSession()->get("form_state_prompt_contentcontroller");
        $request->post_params["prompt_text"] = "1234";
        $request->post_params["secret_form_" . md5("prompt_contentcontroller")] = $formData["secret"];
        $chain->clear();
        $this->assertPattern("/Hallo Welt/", (string) $controller->handleRequest($request));

        $request->post_params["save"] = null;
        $request->post_params["cancel"] = "cancel";
        $formData = GlobalSessionManager::globalSession()->get("form_state_prompt_contentcontroller");
        $request->post_params["secret_form_" . md5("prompt_contentcontroller")] = $formData["secret"];
        /** @var GomaResponse $response */
        $chain->clear();
        $response = $controller->handleRequest($request);
        $this->assertIsA($response, "GomaResponse");
        $this->assertEqual($response->getStatus(), 302);

        $chain->add("1234");
        $this->assertNoPattern("/prompt_text/", $controller->handleRequest($request));
    }

    public function testTrackLinking() {
        try {
            $upload1 = Uploads::addFile("img.jpg", "system/tests/resources/img_1000_480.png", "test.cms", null, false);
            $upload2 = Uploads::addFile("img.jpg", "system/tests/resources/img_1000_750.jpg", "test.cms", null, false);
            $page = new Page(array(
                "data" => '<a href="' . $upload1->path . '" lala="pu">Blub 123 haha</a> <a href="' . $upload2->path . '" lala="pu">Blub 123 haha</a>'
            ));
            $page->writeToDB(false, true);

            ContentController::outputHook($page->data, $this->getRequestWithContentControllerForPage($page));

            $this->assertEqual($page->UploadTracking()->count(), 2);
            $this->assertEqual($page->UploadTracking()->first()->id, $upload1->id);
            $this->assertEqual($page->UploadTracking()->last()->id, $upload2->id);
        } finally {
            if($page) {
                $page->remove(true);
            }
        }
    }

    public function testTrackImage() {
        try {
            $upload1 = Uploads::addFile("img.jpg", "system/tests/resources/img_1000_480.png", "test.cms", null, false);
            $upload2 = Uploads::addFile("img.jpg", "system/tests/resources/img_1000_750.jpg", "test.cms", null, false);
            $page = new Page(array(
                "data" => '<img src="' . $upload1->path . '" lala="pu" /> <img src="' . $upload2->path . '" lala="pu" />'
            ));
            $page->writeToDB(false, true);

            ContentController::outputHook($page->data, $this->getRequestWithContentControllerForPage($page));

            $this->assertEqual($page->UploadTracking()->count(), 2);
            $this->assertEqual($page->UploadTracking()->first()->id, $upload1->id);
            $this->assertEqual($page->UploadTracking()->last()->id, $upload2->id);
        } finally {
            if($page) {
                $page->remove(true);
            }
        }
    }

    protected function getRequestWithContentControllerForPage($page) {
        $controller = new ContentController();
        $reflectionProperty = new ReflectionProperty(RequestHandler::class, "subController");
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, false);

        $controller->Init($request = new Request("GET", "test"));
        $controller->setModelInst($page);

        return $request;
    }
}
