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

    public function testManyManyInitWithWrongValues() {
        $object = new ManyManyTestObjectOne(array(
            "twosids" => array(-1, -2, -3)
        ));

        // TODO: Currently this should pass
        $this->assertEqual(array(-1, -2, -3), $object->twosids);
        $this->assertEqual(0, $object->twos()->count());
    }
}
