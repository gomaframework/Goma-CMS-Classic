<?php
namespace Goma\Test;
use MySQLDateSQLField;
use GomaUnitTest;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for MySQLDate-Field.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class MySQLDateFieldTest extends GomaUnitTest {
    /**
     * tests constructor with
     * - positive integer
     * - negative integer
     * - positive integer string
     * - negative integer string
     */
    public function testInitWithInt() {
        try {
            $timezone = date_default_timezone_get();
            date_default_timezone_set("Europe/London");

            $field = new MySQLDateSQLField("name", 1);
            $this->assertEqual("1970-01-01", $field->getValue());

            $field = new MySQLDateSQLField("name", -1);
            $this->assertEqual("1970-01-01", $field->getValue());

            $field = new MySQLDateSQLField("name", "1");
            $this->assertEqual("1970-01-01", $field->getValue());

            $field = new MySQLDateSQLField("name", "-1");
            $this->assertEqual("1970-01-01", $field->getValue());
        } finally {
            date_default_timezone_set($timezone);
        }
    }

    /**
     * tests constructor with
     * - 2017-01-01 0:15
     * - 10 September 2015 0:15
     */
    public function testInitWithString() {
        $field = new MySQLDateSQLField("name", "2017-01-01 00:15");
        $this->assertEqual("2017-01-01", $field->getValue());

        $field = new MySQLDateSQLField("name", "10 September 2015 00:15");
        $this->assertEqual("2015-09-10", $field->getValue());
    }
}
