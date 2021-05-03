<?php

namespace Goma\Test;

use Group;
use Permission;

defined("IN_GOMA") or die();
/**
 * Tests for Group class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class GroupTest extends \GomaUnitTest
{
    /**
     * tests if creating a group with permission "superadmin" works.
     */
    public function testCreateAdminGroup() {
        try {
            $group = new Group();
            $group->name = lang("admins", "admin");
            $group->type = 2;
            $group->permissions()->add(Permission::forceExisting("superadmin"));
            $group->writeToDB(true, true, 2, false, false);

            $this->assertNotEqual(0, $group->id);
            $this->assertEqual(1, $group->permissions()->filter(array("name" => "superadmin"))->count());
        } finally {
            if($group) {
                $group->remove(true);
            }
        }
    }
}