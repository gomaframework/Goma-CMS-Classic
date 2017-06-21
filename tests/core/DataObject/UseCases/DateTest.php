<?php
namespace Goma\Core\Model\Usecase;
defined("IN_GOMA") OR die();
/**
 * Tests bahaviour of DateSQLField.
 *
 * @package Goma\Test
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 *
 * @version 1.0
 */
class DateTest extends \GomaUnitTest {
    /**
     *  test init.
     */
    public function testInit() {
        $object = new DateTestDataObject();
        $this->assertNull($object->date);
    }

    /**
     *  test writing null value.
     */
    public function testWriteEmpty() {
        try {
            $object = new DateTestDataObject();
            $object->date = "";
            $object->writeToDB(false, true);

            $objectFromDb = \DataObject::get_by_id(DateTestDataObject::class, $object->id);
            $this->assertNull($objectFromDb->date);
        } finally {
            if($object) {
                $object->remove(true);
            }
        }
    }

    /**
     *  test writing null value.
     */
    public function testWriteNull() {
        try {
            $object = new DateTestDataObject();
            $object->writeToDB(false, true);

            $objectFromDb = \DataObject::get_by_id(DateTestDataObject::class, $object->id);
            $this->assertNull($objectFromDb->date);
        } finally {
            if($object) {
                $object->remove(true);
            }
        }
    }

    /**
     *  test writing not null value.
     */
    public function testWriteTimestamp() {
        try {
            $object = new DateTestDataObject();
            $object->date = 2;
            $object->writeToDB(false, true);

            $objectFromDb = \DataObject::get_by_id(DateTestDataObject::class, $object->id);
            $this->assertEqual(2, $objectFromDb->date);
        } finally {
            if($object) {
                $object->remove(true);
            }
        }
    }

    /**
     *  test writing not null value.
     */
    public function testWriteTimestampAndRewriteNull() {
        try {
            $object = new DateTestDataObject();
            $object->date = 2;
            $object->writeToDB(false, true);

            $objectFromDb = \DataObject::get_by_id(DateTestDataObject::class, $object->id);
            $objectFromDb->date = null;
            $objectFromDb->writeToDB(false, true);

            $objectFromDb2 = \DataObject::get_by_id(DateTestDataObject::class, $object->id);
            $this->assertNull($objectFromDb2->date);
        } finally {
            if($object) {
                $object->remove(true);
            }
        }
    }
}

class DateTestDataObject extends \DataObject {
    static $db = array(
        "date" => "date"
    );

    static $search_fields = false;
}
