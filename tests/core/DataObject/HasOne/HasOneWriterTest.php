<?php
namespace Goma\Test\Model;
defined("IN_GOMA") OR die();
/**
 * Unit-Tests for HasOneWriter-Implementation.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class HasOneWriterTest extends \GomaUnitTest {
    /**
     *
     */
    public function testWrite() {
        try {
            $mockHasOne = new MockHasOneSelfClass();
            $mockHasOne->writeToDB(false, true);

            $this->assertNotEqual(0, $mockHasOne->id);
        } finally {
            $mockHasOne->remove(true);
        }
    }

    /**
     *
     */
    public function testWriteWithSelfRelation() {
        try {
            $mockHasOne = new MockHasOneSelfClass();
            $mockHasOne->one = $mockHasOne;
            $mockHasOne->writeToDB(false, true);

            $this->assertNotEqual(0, $mockHasOne->id);
            $this->assertEqual($mockHasOne, $mockHasOne->one);
            $this->assertEqual($mockHasOne->id, $mockHasOne->oneid);

            /** @var MockHasOneSelfClass $gotData */
            $gotData = \DataObject::get_by_id(MockHasOneSelfClass::class, $mockHasOne->id);
            $this->assertEqual($gotData->id, $gotData->one->id);
            $this->assertEqual($gotData, $gotData->one);
        } finally {
            $mockHasOne->remove(true);
        }
    }
}

/**
 * Class MockHasOneSelfClass
 * @property MockHasOneSelfClass one
 * @package Goma\Test\Model
 */
class MockHasOneSelfClass extends \DataObject {
    static $has_one = array(
        "one" => MockHasOneSelfClass::class
    );
}
