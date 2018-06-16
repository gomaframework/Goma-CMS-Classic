<?php
namespace Goma\Test\Model;
use GomaUnitTest;
use HasMany_DataObjectSet;
use TestAble;

defined("IN_GOMA") OR die();
/**
 * Integration-Tests for DataObject-HasMany-HasOne-Implementation.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class HasManyIntegrationTest extends GomaUnitTest implements TestAble
{
    /**
     * area
     */
    static $area = "HasMany";

    /**
     * internal name.
     */
    public $name = "HasManyIntegrationTest";

    /**
     *
     */
    public function testInit() {
        $model = new HasMany_DataObjectSet();
        $this->assertIsA($model, HasMany_DataObjectSet::class);
    }

    /**
     * tests if filtering by hasMany works with simple condition.
     *
     * 1. Create MockHasManyClass $many, write to DB
     * 2. Create MockHasOneClass $one, val = 1, set one to $many, write to db.
     * 3. Create MockHasOneClass $two, val = 2, set one to $many, write to db.
     * 4. Assert that getting MockHasManyClass with filter array("many" => array("val" => array(1, 2)) returns $many
     */
    public function testFilterByHasManySimple() {
        try {
            $many = new MockHasManyClass();
            $many->writeToDB(false, true);

            $one = new MockHasOneClass(
                array(
                    "val" => 1,
                    "one" => $many
                )
            );
            $one->writeToDB(false, true);

            $two = new MockHasOneClass(
                array(
                    "val" => 2,
                    "one" => $many
                )
            );
            $two->writeToDB(false, true);

            $this->assertEqual($many->id, \DataObject::get_one(MockHasManyClass::class, array(
                "many" => array(
                    "val" => array(1,2)
                )
            ))->id);
        } finally {
            if($many) {
                $many->remove(true);
            }

            if($one) {
                $one->remove(true);
            }

            if($two) {
                $two->remove(true);
            }
        }
    }

    /**
     * tests if filtering by hasMany works with simple condition.
     *
     * 1. Create MockHasManyClass $many, write to DB
     * 2. Create MockHasOneClass $one, val = 1, set one to $many, write to db.
     * 3. Create MockHasOneClass $two, val = 2, set one to $many, write to db.
     * 4. Assert that getting MockHasManyClass with filter array("many" => array(array("val" => 1), array("val" => 2)) returns $many
     */
    public function testFilterByHasManyMulti() {
        try {
            $many = new MockHasManyClass();
            $many->writeToDB(false, true);

            $one = new MockHasOneClass(
                array(
                    "val" => 1,
                    "one" => $many
                )
            );
            $one->writeToDB(false, true);

            $two = new MockHasOneClass(
                array(
                    "val" => 2,
                    "one" => $many
                )
            );
            $two->writeToDB(false, true);

            $this->assertEqual($many->id, \DataObject::get_one(MockHasManyClass::class, array(
                "many" => array(
                    array("val" => 1),
                    array("val" => 2)
                )
            ))->id);
        } finally {
            if($many) {
                $many->remove(true);
            }

            if($one) {
                $one->remove(true);
            }

            if($two) {
                $two->remove(true);
            }
        }
    }

    /**
     * tests if filtering by hasMany works with simple condition.
     *
     * 1. Create MockHasManyClass $many, write to DB
     * 2. Create MockHasOneClass $one, val = 1, set one to $many, write to db.
     * 3. Create MockHasOneClass $two, val = 2, write to db.
     * 4. Assert that getting MockHasManyClass with filter array("many" => array(array("val" => 1), array("val" => 2)) is not found
     */
    public function testFilterByHasManyMultiNotFound() {
        try {
            $many = new MockHasManyClass();
            $many->writeToDB(false, true);

            $one = new MockHasOneClass(
                array(
                    "val" => 1,
                    "one" => $many
                )
            );
            $one->writeToDB(false, true);

            $two = new MockHasOneClass(
                array(
                    "val" => 2,
                    "override" => true
                )
            );
            $two->writeToDB(false, true);

            $this->assertNull(\DataObject::get_one(MockHasManyClass::class, array(
                "many" => array(
                    array("val" => 1),
                    array("val" => 2)
                )
            )));
        } finally {
            if($many) {
                $many->remove(true);
            }

            if($one) {
                $one->remove(true);
            }

            if($two) {
                $two->remove(true);
            }
        }
    }
}
