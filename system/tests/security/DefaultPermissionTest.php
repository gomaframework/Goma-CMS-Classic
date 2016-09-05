<?php
defined("IN_GOMA") OR die();

/**
 * Tests if default groups and permissions are created.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class DefaultPermissionTest extends GomaUnitTest {
    /**
     *
     */
    public function setUp() {
        DefaultPermission::checkDefaults();
    }

    /**
     * test default normal group.
     */
    public function testDefaultUserGroup() {
        $defaults = DataObject::get(Group::class, array(
            "usergroup" => 1
        ));
        $this->assertEqual(1, $defaults->count());
        $group = $defaults->first();

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEqual(0, $group->permissions()->count());
    }

    /**
     * tests if at least one super-admin group is existing.
     */
    public function testDefaultAdminGroup() {
        $this->assertGreaterThanOrEqual(1, DataObject::get(Group::class, array(
            "permissions" => array(
                "name" => "superadmin"
            )
        ))->count());
    }

    /**
     *
     */
    public function testDefaultUser() {
        $possibleGroups = DataObject::get(Group::class, array(
            "permissions" => array(
                "name" => "superadmin"
            )
        ))->fieldToArray("id");

        $this->assertGreaterThanOrEqual(1, DataObject::get(User::class, array(
            "groups" => array(
                "id" => $possibleGroups
            )
        ))->count());
    }
}
