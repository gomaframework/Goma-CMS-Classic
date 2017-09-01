<?php
namespace Goma\Test\Form;
use GomaUnitTest;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Form Date-Field.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DateFieldTest extends GomaUnitTest {
    /**
     * tests if null remains null.
     */
    public function testNullValue() {
        $dateField = new \DateField("test", "test", null);
        $this->assertNull($dateField->result());
    }

    /**
     * test if value of 0 is correctly converted to 01.01.1970.
     */
    public function test0Value() {
        $dateField = new \DateField("test", "test", 0);
        $this->assertEqual(date(DATE_FORMAT_DATE, 0), $dateField->result());
    }
}
