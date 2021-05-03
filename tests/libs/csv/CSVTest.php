<?php
namespace Goma\Test;
use CSV;
use GomaCKEditor;
use GomaUnitTest;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for CSV-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class CSVTest extends GomaUnitTest {

    protected static $escapedSemicolonString = "abc\\;a;abc";
    protected static $escapedSemicolonFirstRow = array(1 => "abc;a", 2 => "abc");

    /**
     * tests init.
     */
    public function testInit() {
        $csv = new CSV("");
        $this->assertInstanceOf(CSV::class, $csv);
    }
    /**
     * tests parsing csv.
     */
    public function testParseCSV() {
        $csv = new CSV("abc;123\ndef;456");
        $this->assertInstanceOf(CSV::class, $csv);
        $this->assertEqual(array(
            1 => array(1 => "abc", 2 => "123"),
            2 => array(1 => "def", 2 => "456")
        ), $csv->toArray());
    }

    /**
     * tests creating
     */
    public function testCreate() {
        $csv = new Csv("");
        $csv->addRow(array(1 => "abc", 2 => "123"));
        $this->assertEqual("abc;123", trim($csv->csv()));
    }

    /**
     * tests if parsing is correctly parsing escapes semicolons.
     *
     * 1. Create CSV from escapedSemicolonString (defined above)
     * 2. Assert that first row is same as escapedSemicolonFirstRow (defined above)
     */
    public function testParseEscaped() {
        $csv = new CSV(self::$escapedSemicolonString);
        $this->assertEqual(self::$escapedSemicolonFirstRow, $csv->getRow(1));
    }
}
