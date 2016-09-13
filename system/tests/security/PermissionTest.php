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
class PermissionTest extends GomaUnitTest {
    /**
     * tests for persistence of superadmin.
     */
    public function testForceSuperAdmin() {
        $permission = Permission::forceExisting("superadmin");
        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertNotEqual(0, $permission->id);
    }
}
