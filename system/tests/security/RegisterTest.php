<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for RegisterExtension-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class RegisterTest extends GomaUnitTest {
    /**
     * area
     */
    static $area = "User";

    /**
     * internal name.
     */
    public $name = "Register";

    public static $userData = array(
        "nickname" => "myname",
        "name" => "test",
        "email" => "__test__@ibpg.eu",
        "password" => "1234",
        "repeat" => "1234"
    );

    /**
     * Tests if registration works.
     *
     * 1. Disable RegisterExtension
     * 2. Disable Email validation
     * 3. Load Register Form
     * 4. Remove user __test__@ibpg.eu if exists
     * 5. Create user by simulating request
     * 6. Assert that user exists
     */
    public function testForms() {
        RegisterExtension::$enabled = false;
        RegisterExtension::$validateMail = false;

        $controller = new ProfileController();
        $request = new Request("get", "register");

        $view = new ViewAccessableData();
        $this->assertRegExp("/".preg_quote($view->renderWith("profile/registerNotAllowed.html"), "/")."/", (string) $controller->handleRequest(clone $request));

        RegisterExtension::$enabled = true;

        /** @var GomaFormResponse $form */
        $form = $controller->handleRequest($request);
        $data = (string) $form;
        $this->assertRegExp("/email/", $data);
        $this->assertRegExp("/name/", $data);
        $this->assertRegExp("/password/", $data);
        $this->assertRegExp("/repeat/", $data);

        if($user = DataObject::get(User::class, array("email" => "__test__@ibpg.eu"))->first()) {
            $user->remove(true);
        }

        $request = new Request("post", "register", array(), array_merge(self::$userData, array(
            "form_submit_" . $form->getForm()->getName() => 1,
            "secret_" . $form->getForm()->ID() => $form->getForm()->getSecretKey(),
            "submit" => 1
        )));
        $form = $controller->handleRequest($request);
        $this->assertNotEqual("", $form->render());

        if($user = DataObject::get(User::class, array("email" => "__test__@ibpg.eu"))->first()) {
            $user->remove(true);
        } else {
            $this->assertTrue(false, "Something went wrong at registration.");
        }
    }
}
