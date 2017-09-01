<?php
namespace Goma\Core\Model\Usecase;
defined("IN_GOMA") OR die();
/**
 * Tests bahaviour of IntSQLField.
 *
 * @package Goma\Test
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 *
 * @version 1.0
 */
class IntTest extends \GomaUnitTest {
    /**
     *  test init not null class.
     */
    public function testInitNotNull() {
        $object = new IntTestDataObjectNotNull();
        $this->assertEqual(0, $object->_int);
    }

    /**
     *  test init null class.
     */
    public function testInitNull() {
        $object = new IntTestDataObjectNull();
        $this->assertNull($object->_int);
    }

    /**
     *  test writing null value.
     */
    public function testWriteNull() {
        try {
            $object = new IntTestDataObjectNull();
            $object->writeToDB(false, true);

            $objectFromDb = \DataObject::get_by_id(IntTestDataObjectNull::class, $object->id);
            $this->assertNull($objectFromDb->_int);
        } finally {
            if($object) {
                $object->remove(true);
            }
        }
    }

    /**
     *  test writing null value.
     */
    public function testWriteNotNull() {
        try {
            $object = new IntTestDataObjectNotNull();
            $object->writeToDB(false, true);

            $objectFromDb = \DataObject::get_by_id(IntTestDataObjectNotNull::class, $object->id);
            $this->assertEqual(0, $objectFromDb->_int);
        } finally {
            if($object) {
                $object->remove(true);
            }
        }
    }

    /**
     *  test writing not null value.
     */
    public function testWriteIntToNull() {
        try {
            $object = new IntTestDataObjectNull();
            $object->_int = 2;
            $object->writeToDB(false, true);

            $objectFromDb = \DataObject::get_by_id(IntTestDataObjectNull::class, $object->id);
            $this->assertEqual(2, $objectFromDb->_int);
        } finally {
            if($object) {
                $object->remove(true);
            }
        }
    }

    /**
     *  test writing not null value.
     */
    public function testWriteIntAndRewriteNull() {
        try {
            $object = new IntTestDataObjectNull();
            $object->_int = 2;
            $object->writeToDB(false, true);

            $objectFromDb = \DataObject::get_by_id(IntTestDataObjectNull::class, $object->id);
            $objectFromDb->_int = null;
            $objectFromDb->writeToDB(false, true);

            $objectFromDb2 = \DataObject::get_by_id(IntTestDataObjectNull::class, $object->id);
            $this->assertNull($objectFromDb2->_int);
        } finally {
            if($object) {
                $object->remove(true);
            }
        }
    }
}

class IntTestDataObjectNotNull extends \DataObject {
    static $db = array(
        "_int" => "int(10)"
    );

    static $search_fields = false;
}

class IntTestDataObjectNull extends \DataObject {
    static $db = array(
        "_int" => "int(10) NULL"
    );

    static $search_fields = false;
}
