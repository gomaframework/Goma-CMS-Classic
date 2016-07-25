<?php defined("IN_GOMA") OR die();
/**
 * Tests for ManyManyGetter for ManyMany
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class ManyManyGetterTest extends GomaUnitTest implements TestAble {
    /**
     * area
     */
    static $area = "ManyMany";

    /**
     * internal name.
     */
    public $name = "ManyManyGetter";

    public function testManyManyInit() {
        $data = DataObject::get("ManyManyTestObjectTwo")->getRange(0, 3)->fieldToArray("versionid");
        $object = new ManyManyTestObjectOne(array(
            "twosids" => $data
        ));
        $this->assertEqual($object->twosids, $data);
        $this->assertEqual($object->twos()->count(), 3);
    }

    public function testManyManyInitWithWrongValues() {
        $object = new ManyManyTestObjectOne(array(
            "twosids" => array(-1, -2, -3)
        ));
        $this->assertNotEqual($object->twosids, array(-1, -2, -3));
        $this->assertEqual($object->twos()->count(), 0);
    }
}
