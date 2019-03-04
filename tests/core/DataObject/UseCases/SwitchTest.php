<?php
namespace Goma\Core\Model\Usecase;
defined("IN_GOMA") OR die();
/**
 * Tests boolean values in DataBase.
 *
 * @package Goma\Test
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 *
 * @version 1.0
 */
class SwitchTest extends \GomaUnitTest {
    /**
     *
     */
    public function testWriteAndFindTrue() {
        try {
            $entity = new SwitchTestEntity();
            $entity->switch1 = true;
            $entity->writeToDB(false, true);

            $this->assertEqual(1, \DataObject::get(SwitchTestEntity::class, array("switch1" => true))->count());
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }

    /**
     *
     */
    public function testWriteAndFindFalse() {
        try {
            $entity = new SwitchTestEntity();
            $entity->switch1 = false;
            $entity->writeToDB(false, true);

            $this->assertEqual(1, \DataObject::get(SwitchTestEntity::class, array("switch1" => false))->count());
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }

    /**
     *
     */
    public function testWriteAndFindTrue1() {
        try {
            $this->assertEqual(0, \DataObject::get(SwitchTestEntity::class, array("switch1" => 1))->count());
            $entity = new SwitchTestEntity();
            $entity->switch1 = true;
            $entity->writeToDB(false, true);

            $this->assertEqual(1, \DataObject::get(SwitchTestEntity::class, array("switch1" => 1))->count());
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }

    /**
     *
     */
    public function testWriteAndFindFalse0() {
        try {
            $this->assertEqual(0, \DataObject::get(SwitchTestEntity::class, array("switch1" => 0))->count());
            $entity = new SwitchTestEntity();
            $entity->switch1 = false;
            $entity->writeToDB(false, true);

            $this->assertEqual(1, \DataObject::get(SwitchTestEntity::class, array("switch1" => 0))->count());
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }

    /**
     *
     */
    public function testWriteAndFindTrue11() {
        try {
            $entity = new SwitchTestEntity();
            $entity->switch1 = 1;
            $entity->writeToDB(false, true);

            $this->assertEqual(1, \DataObject::get(SwitchTestEntity::class, array("switch1" => 1))->count());
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }

    /**
     *
     */
    public function testWriteAndFindFalse00() {
        try {
            $entity = new SwitchTestEntity();
            $entity->switch1 = 0;
            $entity->writeToDB(false, true);

            $this->assertEqual(1, \DataObject::get(SwitchTestEntity::class, array("switch1" => 0))->count());
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }

    /**
     *
     */
    public function testWriteAndFind1True() {
        try {
            $entity = new SwitchTestEntity();
            $entity->switch1 = 1;
            $entity->writeToDB(false, true);

            $this->assertEqual(1, \DataObject::get(SwitchTestEntity::class, array("switch1" => true))->count());
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }

    /**
     *
     */
    public function testWriteAndFind0False() {
        try {
            $entity = new SwitchTestEntity();
            $entity->switch1 = 0;
            $entity->writeToDB(false, true);

            $this->assertEqual(1, \DataObject::get(SwitchTestEntity::class, array("switch1" => false))->count());
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }

    /**
     * Tests if getting switch field from db returns boolean for false.
     */
    public function testWrite0AndGetFalse() {
        try {
            $entity = new SwitchTestEntity();
            $entity->switch1 = 0;
            $entity->writeToDB(false, true);

            /** @var SwitchTestEntity $entityFromDb */
            $entityFromDb = \DataObject::get_one(SwitchTestEntity::class, array("switch1" => false));
            $this->assertTrue(is_bool($entityFromDb->switch1));
            $this->assertFalse($entityFromDb->switch1);
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }

    /**
     * Tests if getting switch field from db returns boolean for true.
     */
    public function testWrite1AndGetTrue() {
        try {
            $entity = new SwitchTestEntity();
            $entity->switch1 = 1;
            $entity->writeToDB(false, true);

            /** @var SwitchTestEntity $entityFromDb */
            $entityFromDb = \DataObject::get_one(SwitchTestEntity::class, array("switch1" => true));
            $this->assertTrue(is_bool($entityFromDb->switch1));
            $this->assertTrue($entityFromDb->switch1);
        } finally {
            if($entity) {
                $entity->remove(true);
            }
        }
    }
}

/**
 * Class SwitchTestEntity
 * @package Goma\Core\Model\Usecase
 * @property bool switch1
 */
class SwitchTestEntity extends \DataObject {
    static $db = array(
        "switch1" => "Switch"
    );

    static $search_fields = false;
}

