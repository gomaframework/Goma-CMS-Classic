<?php
namespace Goma\Test;
use GomaUnitTest;
use TimeSQLField;

defined("IN_GOMA") OR die();

/**
 * TimeSQLField.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class TimeSQLFieldTest extends GomaUnitTest {
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

            $field = new TimeSQLField("name", 1);
            $this->assertEqual(date("H:i:s", 1), $field->getValue());

            $field = new TimeSQLField("name", -1);
            $this->assertEqual(date("H:i:s", -1), $field->getValue());

            $field = new TimeSQLField("name", "1");
            $this->assertEqual(date("H:i:s", 1), $field->getValue());

            $field = new TimeSQLField("name", "-1");
            $this->assertEqual(date("H:i:s", -1), $field->getValue());
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
        $field = new TimeSQLField("name", "2017-01-01 00:15:00");
        $this->assertEqual("00:15:00", $field->getValue());

        $field = new TimeSQLField("name", "10 September 2015 00:15:00");
        $this->assertEqual("00:15:00", $field->getValue());
    }
}
