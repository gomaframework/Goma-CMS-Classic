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
        $this->assertEqual(3, count($data));
        $object = new ManyManyTestObjectOne(array(
            "twosids" => $data
        ));
        $this->assertEqual($data, $object->twosids);
        print_r($object->twos());
        $this->assertEqual(3, $object->twos()->count());
    }

    public function testManyManyInitWithWrongValues() {
        $object = new ManyManyTestObjectOne(array(
            "twosids" => array(-1, -2, -3)
        ));

        // TODO: Currently this should pass
        $this->assertEqual(array(-1, -2, -3), $object->twosids);
        $this->assertEqual(0, $object->twos()->count());
    }
}
