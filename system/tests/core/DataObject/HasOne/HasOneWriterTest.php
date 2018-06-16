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
     * tests if writing HasOne Relationship with relationship to itself is writing it correctly.
     *
     * @throws \PermissionException
     * @throws \SQLException
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

    /**
     * tests if writing HasOne Relationship with relationship to another MochHasOneSelfClass is writing it correctly.
     *
     * @throws \PermissionException
     * @throws \SQLException
     */
    public function testWriteWithRelationToSameClass()
    {
        try {
            $mockHasOne = new MockHasOneSelfClass();
            $mockHasOne->one = new MockHasOneSelfClass();
            $mockHasOne->writeToDB(false, true);

            $this->assertNotEqual(0, $mockHasOne->id);
            $this->assertNotEqual(0, $mockHasOne->oneid);
            $this->assertNotEqual($mockHasOne, $mockHasOne->one);
            $this->assertNotEqual($mockHasOne->id, $mockHasOne->oneid);

            /** @var MockHasOneSelfClass $gotData */
            $gotData = \DataObject::get_by_id(MockHasOneSelfClass::class, $mockHasOne->id);
            $this->assertEqual($mockHasOne->one->id, $gotData->one->id);
        } finally {
            if ($mockHasOne->one) {
                $mockHasOne->one->remove(true);
            }

            if ($mockHasOne) {
                $mockHasOne->remove(true);
            }
        }
    }

    /**
     * tests if writing Object sets ID correctly if not yet happened.
     *
     * 1. Create MockHasOneSelfClass $one
     * 2. Create MockHasOneSelfClass $two
     * 3. Set $two->one to $one
     * 4. Write $one
     * 5. Write $two
     * 6. Assert that $two->oneid is $one->id
     *
     * @throws \PermissionException
     * @throws \SQLException
     */
    public function testWriteSetsIdOnModel() {
        try {
            $one = new MockHasOneSelfClass();
            $two = new MockHasOneSelfClass();
            $two->one = $one;

            $one->writeToDB(false, true);
            $two->writeToDB(false, true);

            $this->assertEqual($one->id, $two->oneid);
        } finally {
            if($one) {
                $one->remove(true);
            }

            if($two) {
                $two->remove(true);
            }
        }
    }

    /**
     * tests if writing Object sets ID correctly if not yet happened on cascade-type=updatefield
     *
     * 1. Create MockHasOneSelfClassCascadeUpdateField $one
     * 2. Create MockHasOneSelfClassCascadeUpdateField $two
     * 3. Set $two->one to $one
     * 4. Write $one
     * 5. Write $two
     * 6. Assert that $two->oneid is $one->id
     *
     * @throws \PermissionException
     * @throws \SQLException
     */
    public function testWriteSetsIdOnModelCascadeUpdatefield() {
        try {
            $one = new MockHasOneSelfClassCascadeUpdateField();
            $two = new MockHasOneSelfClassCascadeUpdateField();
            $two->one = $one;

            $one->writeToDB(false, true);
            $two->writeToDB(false, true);

            $this->assertEqual($one->id, $two->oneid);
        } finally {
            if($one) {
                $one->remove(true);
            }

            if($two) {
                $two->remove(true);
            }
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


/**
 * Class MockHasOneSelfClassCascadeUpdateField
 * @property MockHasOneSelfClassCascadeUpdateField one
 * @package Goma\Test\Model
 */
class MockHasOneSelfClassCascadeUpdateField extends \DataObject {
    static $has_one = array(
        "one" => array(
             \DataObject::RELATION_TARGET => MockHasOneSelfClassCascadeUpdateField::class,
             \DataObject::CASCADE_TYPE => \DataObject::CASCADE_TYPE_UPDATEFIELD
        )
    );
}
