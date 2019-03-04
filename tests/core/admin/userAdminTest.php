<?php
namespace Goma\Test\Admin;

use adminItem;
use Goma\Test\GomaUnitTestWithAdmin;
use User;
use userAdmin;

defined("IN_GOMA") OR die();

/**
 * Unit-Tests for AdminItem.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class UserAdminTest extends GomaUnitTestWithAdmin {

    /**
     * @var User
     */
    protected $user;

    /**
     * @throws \DataObjectSetCommitException
     * @throws \MySQLException
     * @throws \PermissionException
     * @throws \SQLException
     */
    public function setUp()
    {
        parent::setUp();

        if($user = \DataObject::get_one(User::class, array("email" => "usertestlock@goma-cms.org"))) {
            $user->remove(true);
        }

        $this->user = new User();
        $this->user->nickname = "usertestlock@goma-cms.org";
        $this->user->email = $this->user->nickname;
        $this->user->password = randomString(10);
        $this->user->name = "My User For ToggleLock";
        $this->user->writeToDB(false, true);
    }

    /**
     * @throws \MySQLException
     * @throws \SQLException
     */
    public function tearDown()
    {
        parent::tearDown();

        if($this->user) {
            $this->user->remove(true);
        }
    }

    /**
     * test init.
     */
    public function testInitNull() {
        $item = new userAdmin();
        $this->assertInstanceOf(AdminItem::class, $item);
    }

    /**
     * test init.
     * @throws \Exception
     */
    public function testToggleLock() {
        try {
            $user = \Member::$loggedIn;
            \Member::InitUser($this->adminUser);

            $item = new userAdmin();
            $request = new \Request("get", "toggleLock/".$this->user->id);
            $response = $item->handleRequest($request);
            $this->assertTrue(!!strpos((string)$response, (string) $this->user->name));
        } finally {
            \Member::InitUser($user);
        }
    }
}