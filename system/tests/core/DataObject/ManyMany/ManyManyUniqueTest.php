<?php
namespace Goma\Test\Model;
use GomaUnitTest;

defined("IN_GOMA") OR die();

/**
 * Tests ManyMany's capability to support unique tables.
 *
 * @package dLED
 *
 * @author IBPG
 * @copyright 2017 IBPG
 *
 * @version 1.0
 */
class ManyManyUniqueTest extends GomaUnitTest {
    /**
     * tests init.
     */
    public function testInit() {
        $record = new RelationTable();
        $this->assertInstanceOf(RelationTable::class, $record);
        $this->assertInstanceOf(\ManyMany_DataObjectSet::class, $record->many());
    }

    /**
     * tests init.
     */
    public function testWriteUnique() {
        try {
            $records = array();
            for($i = 0; $i < 5; $i++) {
                $record = new RelationTable();
                $record->many()->add(new UniqueTable(array(
                    "blub" => 1,
                    "name" => "hihi"
                )));
                $record->writeToDB(false, true);
                $records[] = $record;
            }

            $this->assertEqual(5, \DataObject::get(RelationTable::class)->count());
            $this->assertEqual(1, \DataObject::get(UniqueTable::class)->count());

            foreach(\DataObject::get(RelationTable::class) as $record) {
                $this->assertEqual(1, $record->many()->count());
            }
        } finally {
            foreach($records as $record) {
                $record->remove(true);
            }

            foreach(\DataObject::get(UniqueTable::class) as $unique) {
                $unique->remove(true);
            }
        }
    }
}

class UniqueTable extends \DataObject {
    static $db = array(
        "blub"  => "int(1)",
        "name"  => "varchar(10)"
    );

    static $unique_fields = array(
        "blub", "name"
    );

    static $search_fields = false;
}

/**
 * Class RelationTable
 * @package Goma\Test\Model
 * @method \ManyMany_DataObjectSet many()
 */
class RelationTable extends \DataObject {
    static $db = array(
        "name" => "varchar(10)"
    );

    static $many_many = array(
        "many"  => array(
            \DataObject::RELATION_TARGET => UniqueTable::class,
            \DataObject::CASCADE_TYPE => \DataObject::CASCADE_TYPE_UNIQUE,
            \DataObject::CASCADE_UNIQUE_LIKE => true
        )
    );

    static $search_fields = false;
}
