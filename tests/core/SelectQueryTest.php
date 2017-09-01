<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for 503-Handling.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class SelectQueryTest extends GomaUnitTest implements TestAble {


    static $area = "framework";
    /**
     * name
     */
    public $name = "SelectQuery";


	/**
     * test availability functions.
     */
    public function testColiding() {
        /*$query = new SelectQuery("MyTestModelForDataObjectFieldWrite", array("myfield" => 0));
        $query->db_fields["myfield"] = "MyTestModelForDataObjectFieldWrite.myfield";

        echo $query->build();*/
    }

    /**
     * test order by rand
     */
    public function testOrderByRand() {
        $query = new SelectQuery("user");
        $query->sort("rand()");
        $this->assertEqual(
            "SELECT user.* FROM ".DB_PREFIX."user AS user  ORDER BY RAND()",
            $query->build()
        );
    }

    /**
     * test order by rand
     */
    public function testOrderByRANDUpperCase() {
        $query = new SelectQuery("user");
        $query->sort("RAND()");
        $this->assertEqual(
            "SELECT user.* FROM ".DB_PREFIX."user AS user  ORDER BY RAND()",
            $query->build()
        );
    }

    /**
     * test order by rand
     */
    public function testOrderByRandWithoutBracketsUpper() {
        $query = new SelectQuery("user");
        $query->sort("RAND");
        $this->assertEqual(
            "SELECT user.* FROM ".DB_PREFIX."user AS user  ORDER BY RAND()",
            $query->build()
        );
    }

    /**
     * test order by rand
     */
    public function testOrderByRandWithoutBrackets() {
        $query = new SelectQuery("user");
        $query->sort("rand");
        $this->assertEqual(
            "SELECT user.* FROM ".DB_PREFIX."user AS user  ORDER BY RAND()",
            $query->build()
        );
    }

    /**
     * test order by rand with array
     */
    public function testOrderByRandArray() {
        $query = new SelectQuery("user");
        $query->sort(array("rand"));
        $this->assertEqual(
            "SELECT user.* FROM ".DB_PREFIX."user AS user  ORDER BY RAND()",
            $query->build()
        );
    }

    /**
     * test order by rand with array
     */
    public function testOrderByRandArrayWithBrackets() {
        $query = new SelectQuery("user");
        $query->sort(array("rand()"));
        $this->assertEqual(
            "SELECT user.* FROM ".DB_PREFIX."user AS user  ORDER BY RAND()",
            $query->build()
        );
    }

    /**
     * test order by rand with array and different column
     */
    public function testOrderByRandArrayWithBracketsAndDifferent() {
        $query = new SelectQuery("user");
        $query->sort(array("rand()"));
        $query->sort("id", "desc");
        $this->assertEqual(
            "SELECT user.* FROM ".DB_PREFIX."user AS user  ORDER BY RAND(),id DESC",
            $query->build()
        );
    }
}
