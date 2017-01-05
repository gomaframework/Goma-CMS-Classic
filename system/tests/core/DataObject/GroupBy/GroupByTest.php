<?php
namespace Goma\Test\Model\GroupBy;

use DBTableManager;
use Goma\Model\Group\GroupDataObjectSet;
use MySQLException;
use SQL;

defined("IN_GOMA") OR die();

/**
 * Integration-Tests for GroupBy-DataObject-Classes.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class GroupByTest extends \GomaUnitTest {
    /**
     *
     */
    public function setUp() {
        foreach(DBTableManager::Tables(GroupByTestObject::class) as $table) {
            if(!SQL::query("TRUNCATE TABLE " . DB_PREFIX . $table)) {
                throw new MySQLException();
            }
        }

        for($i = 0; $i < 20; $i++) {
            $groupByTest = new GroupByTestObject(array(
                "number" => $i % 5,
                "random" => $i
            ));
            $groupByTest->writeToDB(false, true);
        }
    }

    public function testGroupByCount() {
        $set = \DataObject::get(GroupByTestObject::class);
        $grouped = $set->groupBy("number");

        $this->assertEqual(5, $grouped->count());
    }

    public function testGroupByMaxCount() {
        $set = \DataObject::get(GroupByTestObject::class);
        $grouped = $set->groupBy("number");

        $this->assertEqual("4,5", $grouped->MaxCount("number"));
    }

    public function testGroupByMax() {
        $set = \DataObject::get(GroupByTestObject::class);
        $grouped = $set->groupBy("number");

        $this->assertEqual(4, $grouped->Max("number"));
    }

    public function testGroupByMin() {
        $set = \DataObject::get(GroupByTestObject::class);
        $grouped = $set->groupBy("number");

        $this->assertEqual(0, $grouped->Min("number"));
    }

    public function testGroupedLoop() {
        $set = \DataObject::get(GroupByTestObject::class);
        $grouped = $set->groupBy("number");

        foreach($grouped as $group) {
            $this->assertIsA($group, GroupDataObjectSet::class);
            $this->assertEqual(4, $group->count());
        }
    }

    public function tearDown() {
        /*foreach(DBTableManager::Tables(GroupByTestObject::class) as $table) {
            if(!SQL::query("TRUNCATE TABLE " . DB_PREFIX . $table)) {
                throw new MySQLException();
            }
        }*/
    }
}

/**
 * Class ManyManyTestObjectTwo
 */
class GroupByTestObject extends \DataObject {

    static $versions = true;

    static $db = array(
        "number"    => "int(10)",
        "random"    => "varchar(200)"
    );

    static $search_fields = false;
}
