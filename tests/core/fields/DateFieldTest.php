<?php
namespace Goma\Test;
use DateSQLField;
use GomaUnitTest;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Date-Field.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DateFieldTest extends GomaUnitTest {
    /**
     * tests constructor with
     * - positive integer
     * - negative integer
     * - positive integer string
     * - negative integer string
     */
    public function testInitWithInt() {
        $field = new DateSQLField("name", 1);
        $this->assertEqual(1, $field->getValue());

        $field = new DateSQLField("name", -1);
        $this->assertEqual(-1, $field->getValue());

        $field = new DateSQLField("name", "1");
        $this->assertEqual(1, $field->getValue());

        $field = new DateSQLField("name", "-1");
        $this->assertEqual(-1, $field->getValue());
    }

    /**
     * tests constructor with
     * - 2017-01-01 0:15
     * - 10 September 2015 0:15
     */
    public function testInitWithString() {
        $field = new DateSQLField("name", "2017-01-01 00:15");
        $this->assertEqual(mktime(0, 0, 0, 1, 1, 2017), $field->getValue());

        $field = new DateSQLField("name", "10 September 2015 00:15");
        $this->assertEqual(mktime(0, 0, 0, 9, 10, 2015), $field->getValue());
    }

    /**
     * tests with invalid format.
     */
    public function testWithInvalidFormat() {
        $field = new DateSQLField("name", "10 September 2015 0:15", array("j-M-Y"));
        $this->assertEqual(mktime(0, 0, 0, 9, 10, 2015), $field->getValue());
    }

    /**
     * tests with valid format.
     */
    public function testWithValidFormat() {
        $field = new DateSQLField("name", "15-Feb-2009", array("j-M-Y"));
        $this->assertEqual(mktime(0, 0, 0, 2, 15, 2009), $field->getValue());
    }

    /**
     * tests with valid format, which might conflict with DD-MM-YYYY.
     */
    public function testWithValidStrangeFormat() {
        $field = new DateSQLField("name", "02-15-2009", array("m-j-Y"));
        $this->assertEqual(mktime(0, 0, 0, 2, 15, 2009), $field->getValue());
    }

    /**
     * tests with valid format, which might conflict with YYYY-MM-DD.
     */
    public function testWithValidDifferentStrangeFormat() {
        $field = new DateSQLField("name", "2009-15-02", array("Y-j-m"));
        $this->assertEqual(mktime(0, 0, 0, 2, 15, 2009), $field->getValue());
    }

    /**
     * tests HTML5-Format with
     * - 2017-01-01T00:15:00
     * - 2016-03-01T00:15:13
     * - 2017-01-01
     */
    public function testHTML5Format() {
        $field = new DateSQLField("name", "2017-01-01T00:15:13");
        $this->assertEqual(mktime(0, 0, 0, 1, 1, 2017), $field->getValue());

        $field = new DateSQLField("name", "2016-03-01T00:15:00");
        $this->assertEqual(mktime(0, 0, 0, 3, 1, 2016), $field->getValue());

        $field = new DateSQLField("name", "2017-01-01");
        $this->assertEqual(mktime(0, 0, 0, 1, 1, 2017), $field->getValue());
    }
}
