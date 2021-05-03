<?php

namespace Goma\Test\Admin;

use adminController;
use Goma\Test\GomaUnitTestWithAdmin;

defined("IN_GOMA") or die();


/**
 * Unit-Tests for AdminItem.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class adminControllerTest extends GomaUnitTestWithAdmin
{
    /**
     * tests if handleItem is correctly handling TestHandleItemAdmin.
     */
    public function testHandleItem()
    {
        try {
            $currentMember = \Member::$loggedIn;
            \Member::InitUser($this->adminUser);

            TestHandleItemAdmin::$handled = false;
            $request = new \Request("get", substr(str_replace("\\", "-", TestHandleItemAdmin::class), 0, -5));
            $adminController = new AdminController();
            $adminController->handleRequest($request);

            $this->assertTrue(TestHandleItemAdmin::$handled);
        } finally {
            \Member::InitUser($currentMember);
        }
    }
}

class TestHandleItemAdmin extends \adminItem {

    public static $handled = false;

    /**
     * @param \User $user
     * @return bool
     */
    public static function visible($user)
    {
        return false;
    }

    /**
     * @param \Request $request
     * @param bool $subController
     * @return false|string
     */
    public function handleRequest($request, $subController = false)
    {
        self::$handled = true;

        return "ok";
    }
}
