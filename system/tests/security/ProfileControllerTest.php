<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Member-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */


class ProfileControllerTest extends \Goma\Test\GomaUnitTestWithAdmin
{
    /**
     * area
     */
    static $area = "User";

    /**
     * internal name.
     */
    public $name = "ProfileController";

    /**
     * tests if login page shows login if logged out.
     */
    public function testLoginPageShowsForm() {
        try {
            $current = Member::$loggedIn;
            Member::InitUser(null);

            $profileController = new ProfileController();
            $request = new Request("post", "");
            $profileController->Init($request);

            $response = $profileController->login();
            $this->assertIsA($response, "string");
        } finally {
            Member::InitUser($current);
        }
    }

    /**
     * tests if login page redirects logged in users.
     */
    public function testLoginPageRedirectsForLoggedIn() {
        try {
            $current = Member::$loggedIn;
            Member::InitUser($this->adminUser);

            $profileController = new ProfileController();
            $request = new Request("post", "");
            $profileController->Init($request);

            $response = $profileController->login();
            $this->assertIsA($response, "GomaResponse");
        } finally {
            Member::InitUser($current);
        }
    }
}
