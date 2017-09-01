<?php
namespace Goma\Test;
use DateTimeSQLField;
use GomaUnitTest;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for SQL Date-Field.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DateTimeFieldTest extends GomaUnitTest {
    /**
     * tests constructor with
     * - positive integer
     * - negative integer
     * - positive integer string
     * - negative integer string
     */
    public function testInitWithInt() {
        $field = new DateTimeSQLField("name", 1);
        $this->assertEqual(1, $field->getValue());

        $field = new DateTimeSQLField("name", -1);
        $this->assertEqual(-1, $field->getValue());

        $field = new DateTimeSQLField("name", "1");
        $this->assertEqual(1, $field->getValue());

        $field = new DateTimeSQLField("name", "-1");
        $this->assertEqual(-1, $field->getValue());
    }

    /**
     * tests constructor with
     * - 2017-01-01 0:15
     * - 10 September 2015 0:15
     */
    public function testInitWithString() {
        $field = new DateTimeSQLField("name", "2017-01-01 0:15");
        $this->assertEqual(mktime(0, 15, 0, 1, 1, 2017), $field->getValue());

        $field = new DateTimeSQLField("name", "10 September 2015 0:15");
        $this->assertEqual(mktime(0, 15, 0, 9, 10, 2015), $field->getValue());
    }

    /**
     * tests with invalid format.
     */
    public function testWithInvalidFormat() {
        $field = new DateTimeSQLField("name", "10 September 2015 0:15", array("j-M-Y"));
        $this->assertEqual(mktime(0, 15, 0, 9, 10, 2015), $field->getValue());
    }

    /**
     * tests with valid format.
     */
    public function testWithValidFormat() {
        $field = new DateTimeSQLField("name", "15-Feb-2009", array("j-M-Y"));
        $this->assertEqual(mktime(date("H"), date("i"), date("s"), 2, 15, 2009), $field->getValue());
    }

    /**
     * tests with valid format, which might conflict with DD-MM-YYYY.
     */
    public function testWithValidStrangeFormat() {
        $field = new DateTimeSQLField("name", "02-15-2009", array("m-j-Y"));
        $this->assertEqual(mktime(date("H"), date("i"), date("s"), 2, 15, 2009), $field->getValue());
    }

    /**
     * tests with valid format, which might conflict with YYYY-MM-DD.
     */
    public function testWithValidDifferentStrangeFormat() {
        $field = new DateTimeSQLField("name", "2009-15-02", array("Y-j-m"));
        $this->assertEqual(mktime(date("H"), date("i"), date("s"), 2, 15, 2009), $field->getValue());
    }

    /**
     * tests HTML5-Format with
     *
     * - 2017-01-01T00:15:00
     * - 2016-03-01T00:15:13
     * - 2017-01-01
     */
    public function testHTML5Format() {
        $field = new DateTimeSQLField("name", "2017-01-01T00:15:00");
        $this->assertEqual(mktime(0, 15, 0, 1, 1, 2017), $field->getValue());

        $field = new DateTimeSQLField("name", "2016-03-01T00:15:13");
        $this->assertEqual(mktime(0, 15, 13, 3, 1, 2016), $field->getValue());

        $field = new DateTimeSQLField("name", "2017-01-01");
        $this->assertEqual(mktime(0, 0, 0, 1, 1, 2017), $field->getValue());
    }
}
