<?php

namespace Goma\Test\Form;

use PermissionField;

defined("IN_GOMA") or die();

/**
 * Unit-Tests for PermissionField.
 *
 * @package        Goma\Test
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class PermissionFieldTest extends \GomaUnitTest
{
    /**
     * tests result can be null if disabled.
     *
     * 1. Create PermissionField
     * 2. Disable it
     * 3. Assert that result() returns null
     */
    public function testResultNullDisabled() {
        $field = new PermissionField("test");
        $field->disable();
        $this->assertNull($field->result());
    }

    /**
     * tests result is new Permission if not disabled.
     *
     * 1. Create PermissionField
     * 2. Assert that result() is type of Permission
     * 3. Assert that result()->id is 0
     */
    public function testResultNotNullEnabled() {
        $field = new PermissionField("test");
        $this->assertInstanceOf(\Permission::class, $field->result());
        $this->assertEqual(0, $field->result()->id);
    }
}
