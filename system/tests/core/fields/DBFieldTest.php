<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for HTMLText-Field.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class DBFieldTest extends GomaUnitTest implements TestAble {
    /**
     * tests size-matching
     *
     *@name testSizeMatching
     */
    public function testParseCastingForAllChildren() {
        foreach(ClassInfo::getChildren("DBField") as $child) {
            $expected = array(
                "class" => $child,
                "args"  => array(1,2)
            );

            if(ClassInfo::hasInterface($child, "DefaultConvert")) {
                $expected["convert"] = true;
            }

            $this->assertEqual(DBField::parseCasting($child . "(1,2)"), $expected, "DBField Check $child %s");

            $this->assertEqual(DBField::parseCasting($child . "(1,2) NULL"), $expected, "DBField Check $child %s");
            $this->assertEqual(DBField::parseCasting($child . "(1,2) NOT NULL"), $expected, "DBField Check $child %s");

            $expected["method"] = randomString(3);
            $this->assertEqual(DBField::parseCasting($child . "(1,2)->" . $expected["method"]), $expected, "DBField Check with method $child %s");
        }
    }

    /**
     * Tests if DataTime NULL is correctly casted to datetimeSQLField in parseCasting.
     */
    public function testParseCastingDataTimeNull() {
        $this->assertEqual(array("class" => "datetimeSQLField"), DBField::parseCasting("DateTime NULL"));
    }


    /**
     * Tests if DataTime NOT NULL is correctly casted datetimeSQLField in parseCasting.
     */
    public function testParseCastingDataTimeNotNull() {
        $this->assertEqual(array("class" => "datetimeSQLField"), DBField::parseCasting("DateTime NOT NULL"));
    }


    /**
     * tests some example of where DBField::isNullType should return true.
     * int(10) NULL
     * text NULL
     * int(10)->blah() NULL
     */
    public function testIsNullType() {
        $this->assertTrue(DBField::isNullType("int(10) NULL"));
        $this->assertTrue(DBField::isNullType("text NULL"));
        $this->assertTrue(DBField::isNullType("int(10)->blah() NULL"));
        $this->assertTrue(DBField::isNullType("int(10)->not() NULL"));
    }

    /**
     * tests some example of where DBField::isNullType should return false.
     * int(10)
     * text NOT NULL
     * text->null()
     * int(10)->null() NOT NULL
     */
    public function testIsNullTypeFalse() {
        $this->assertFalse(DBField::isNullType("int(10)"));
        $this->assertFalse(DBField::isNullType("text NOT NULL"));
        $this->assertFalse(DBField::isNullType("int(10)->null()"));
        $this->assertFalse(DBField::isNullType("int(10)->null() NOT NULL"));
    }

    /**
     * tests getDBFieldTypeForCasting for
     * int(10)
     * text NOT NULL
     * DBField NOT NULL
     * time NULL
     */
    public function testGetDBFieldTypeForCasting() {
        $this->assertEqual("int(10)", DBField::getDBFieldTypeForCasting("int(10)"));
        $this->assertEqual("text NOT NULL", DBField::getDBFieldTypeForCasting("text NOT NULL"));
        $this->assertEqual("DBField NOT NULL", DBField::getDBFieldTypeForCasting("DBField NOT NULL"));
        $this->assertEqual("time NULL", DBField::getDBFieldTypeForCasting("time NULL"));
    }
}
