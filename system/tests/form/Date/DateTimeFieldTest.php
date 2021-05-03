<?php
namespace Goma\Test\Form;
use GomaUnitTest;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Form DateTime-Field.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DateTimeFieldTest extends GomaUnitTest {
    /**
     * tests if null remains null.
     */
    public function testNullValue() {
        $dateField = new \DateTimeField("test", "test", null);
        $this->assertNull($dateField->result());
    }

    /**
     * test if value of 0 is correctly converted to timestamp 0.
     */
    public function test0Value() {
        $dateField = new \DateTimeField("test", "test", 0);
        $this->assertEqual(0, $dateField->result());
    }
}
