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
    private static $groupName = "myGroup";

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
        $this->assertGreaterThanOrEqual(1, $defaults->count());
        /** @var Group $group */
        $group = $defaults->first();

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEqual($this->getProvidedPermissionsDefaultUserNames(), $group->permissions()->fieldToArray("name"), print_r($group->permissions()->fieldToArray("name")));
    }

    /**
     * @return array
     */
    private function getProvidedPermissionsDefaultUserNames() {
        $names = array();
        foreach(Permission::$providedPermissions as $name => $permission) {
            if($permission["default"]["type"] == "users") {
                $names[] = $name;
            }
        }
        return $names;
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
     * @testdox tests if an admin-user exists in this installation. This is part of the health-check.
     */
    public function testAdminUserExists() {
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

    /**
     * Tests if forceGroupType creates group for user without a group
     * and returns groupType 1 or greater since new group is grouptype 1 or greater.
     *
     * 1. Create User, set to $newUser
     * 2. Write user to DB
     *
     * 3. Assert that DefaultPermission::forceGroupType($newUser) returns at least 1
     * 4. Assert that $newUser->groups()->count() is at least 1.
     *
     * @throws MySQLException
     */
    public function testforceGroupTypeTest() {
        try {
            $newUser = new \User();
            $newUser->nickname = $newUser->email = "beta@soredi-touch-systems.com";
            $newUser->password = "1234";
            $newUser->writeToDB(false, true);

            $this->assertGreaterThanOrEqual(1, DefaultPermission::forceGroupType($newUser));
            $this->assertGreaterThanOrEqual(1, $newUser->groups()->count());
        } finally {
            if($newUser) {
                $newUser->remove(true);
            }
        }
    }

    /**
     * Tests if forceGroupType creates group-type if group is existing and does not create a group.
     *
     * 1. Create Group $group  with type 2, write to db.
     * 2. Create User, set to $newUser
     * 3. Add $group to user.
     * 4. Write user to DB
     *
     * 5. Assert that DefaultPermission::forceGroupType($newUser) returns 2
     * 6. Assert that $newUser->groups()->count() is equal to 1.
     *
     * @throws MySQLException
     */
    public function testforceGroupTypeWithGroupTest() {
        try {
            $group = new Group(array(
                "name" => self::$groupName,
                "type" => 2
            ));
            $group->writeToDB(false, true);

            $newUser = new \User();
            $newUser->nickname = $newUser->email = "beta@soredi-touch-systems.com";
            $newUser->password = "1234";
            $newUser->groups()->add($group);
            $newUser->writeToDB(false, true);

            $this->assertEqual(2, DefaultPermission::forceGroupType($newUser));
            $this->assertEqual(1, $newUser->groups()->count());
        } finally {
            if($newUser) {
                $newUser->remove(true);
            }

            if($group) {
                $group->remove(true);
            }
        }
    }

    /**
     * Tests if forceGroupType returns greatest group-type.
     *
     * 1. Create Group $group2  with type 2, write to db.
     * 1. Create Group $group1  with type 1, write to db.
     * 3. Create User, set to $newUser
     * 4. Add $group1 to user.
     * 5. Add $group2 to user.
     * 6. Write user to DB
     *
     * 7. Assert that DefaultPermission::forceGroupType($newUser) returns 2
     * 8. Assert that $newUser->groups()->count() is equal to 2.
     *
     * @throws MySQLException
     */
    public function testforceGroupTypeGreatestType() {
        try {
            $group2 = new Group(array(
                "name" => self::$groupName,
                "type" => 2
            ));
            $group2->writeToDB(false, true);

            $group1 = new Group(array(
                "name" => self::$groupName . "_1",
                "type" => 1
            ));
            $group1->writeToDB(false, true);

            $newUser = new \User();
            $newUser->nickname = $newUser->email = "beta@soredi-touch-systems.com";
            $newUser->password = "1234";
            $newUser->groups()->add($group1);
            $newUser->groups()->add($group2);
            $newUser->writeToDB(false, true);

            $this->assertEqual(2, DefaultPermission::forceGroupType($newUser));
            $this->assertEqual(2, $newUser->groups()->count());
        } finally {
            if($newUser) {
                $newUser->remove(true);
            }

            if($group1) {
                $group1->remove(true);
            }

            if($group2) {
                $group2->remove(true);
            }
        }
    }
}
