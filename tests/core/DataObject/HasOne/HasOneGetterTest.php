<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for HasOneGetter-Implementation.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class HasOneGetterTest extends GomaUnitTest implements TestAble
{
    /**
     * 
     */
    public function testAssign() {
        $mockDBObject1 = new MockDBObjectHasOne();
        $mockDBObject2 = new MockDBObjectHasOne();

        $this->assertEqual($mockDBObject1->hasonerelation, null);

        $mockDBObject1->hasonerelation = $mockDBObject2;

        $this->assertEqual($mockDBObject1->hasonerelation, $mockDBObject2);
        $this->assertEqual($mockDBObject2->hasonerelation, null);
    }

    /**
     *
     */
    public function testSetId() {
        $mockDBObject1 = new MockDBObjectHasOne();
        $mockDBObject2 = new MockDBObjectHasOne();

        $this->assertEqual($mockDBObject1->hasonerelation, null);

        $mockDBObject1->hasonerelation = $mockDBObject2;

        $this->assertEqual($mockDBObject1->hasonerelation, $mockDBObject2);
        $mockDBObject1->hasonerelationid = 0;

        $this->assertEqual($mockDBObject1->hasonerelation, null);
    }
}

/**
 * Class MockDBObjectHasOne
 *
 * @property MockDBObjectHasOne hasonerelation
 * @property int hasonerelationid
 */
class MockDBObjectHasOne extends DataObject {
    static $has_one = array(
        "hasonerelation" => "MockDBObjectHasOne"
    );
}
