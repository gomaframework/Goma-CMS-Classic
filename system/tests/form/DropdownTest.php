<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Dropdown.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class DropdownTest extends GomaUnitTest {
    /**
     * area
     */
    static $area = "Form";

    /**
     * internal name.
     */
    public $name = "Dropdown";

    /**
     * gets key from info.
     */
    public function testGetKeyFromInfo() {
        $this->assertEqual("abc", $this->unitTestGetKeyFromInfo(array("abc", "cde"), 0, "abc"));
        $this->assertEqual(1, $this->unitTestGetKeyFromInfo(array(1 => "abc", 2 => "cde"), 1, 1));

        $this->assertThrows(function() {
            $this->unitTestGetKeyFromInfo(array(
                array("title" => "abc", "ccc" => "abc"),
                array("title" => "deg", "ccc" => "def")
            ), 0, array("title" => "abc", "ccc" => "abc"));
        }, "InvalidArgumentException");

        $this->assertEqual("abc", $this->unitTestGetKeyFromInfo(array(
            array("title" => "abc", "ccc" => "abc", "id" => "abc"),
            array("title" => "deg", "ccc" => "def", "id" => "deg")
        ), 0, array("title" => "abc", "ccc" => "abc", "id" => "abc")));
        $this->assertEqual(1, $this->unitTestGetKeyFromInfo(array(
            1 => array("title" => "abc", "ccc" => "abc"),
            2 => array("title" => "deg", "ccc" => "def")
        ), 1, array("title" => "abc", "ccc" => "abc")));

        $source = new DataObjectSet("user");
        $this->assertEqual($source->first()->id, $this->unitTestGetKeyFromInfo($source, 0, $source->first()));
    }

    protected function unitTestGetKeyFromInfo($dataSource, $key, $value) {
        $dropdown = new DropDown();
        $method = new ReflectionMethod(Dropdown::class, "getKeyFromInfo");
        $method->setAccessible(true);

        return $method->invoke($dropdown, $dataSource, $key, $value);
    }

    public function testCheckValue() {
        $dropdown = new DropDown("test", "test", array(
            1 => "blub",
            2 => "blah",
            3 => "test"
        ), 3);
        $dropdown->setRequest(new Request("post", "blub"));

        $this->assertEqual($dropdown->getModel(), 3);
        $this->assertEqual($dropdown->result(), 3);

        $dropdown->getRequest()->params["value"] = 2;
        $dropdown->getRequest()->params["ajax"] = true;
        $dropdown->checkValue();

        $this->assertEqual($dropdown->getModel(), 2);
        $this->assertEqual($dropdown->result(), 2);

        $dropdown->disable();
        $dropdown->getRequest()->params["value"] = 3;
        $dropdown->checkValue();

        $this->assertEqual($dropdown->getModel(), 2);
        $this->assertEqual($dropdown->result(), 2);

        $dropdown->enable();
        $dropdown->uncheckValue();

        $this->assertEqual($dropdown->getModel(), 2);
        $this->assertEqual($dropdown->result(), 2);

        $dropdown->getRequest()->params["value"] = 2;
        $dropdown->uncheckValue();

        $this->assertEqual($dropdown->getModel(), null);
        $this->assertEqual($dropdown->result(), null);
    }
}
