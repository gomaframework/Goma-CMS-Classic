<?php
namespace Goma\Test\Model;

use DataObjectSet;
use GomaUnitTest;
use HasMany_DataObjectSet;
use MockWriteEntity;
use ModelHasManyRelationShipInfo;
use TestAble;
use User;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for DataObject-Field-Implementation.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class HasManyDataObjectSetTest extends GomaUnitTest implements TestAble
{
    /**
     * area
     */
    static $area = "HasMany";

    /**
     * internal name.
     */
    public $name = "HasManyDataObjectSet";

    /**
     * @var DumpDBElementPerson
     */
    protected $patrick;

    public function setUp() {
        $this->patrick = new DumpDBElementPerson("Patrick", 16, "M");
    }

    /**
     *
     */
    public function testPush() {
        $set = new HasMany_DataObjectSet(MockWriteEntity::class);

        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $e = new MockWriteEntity();
        $oldE = clone $e;
        $set->push($e);

        $this->assertEqual($e->ToArray(), $oldE->ToArray());

        $set->setRelationENV($info = new ModelHasManyRelationShipInfo(MockWriteEntity::class, "blah", array(
            "target" => User::class,
            "inverse"   => "blub",
            "validatedInverse"  => true
        )), 1, new MockWriteEntity());

        $newE = clone $oldE;
        $set->push($newE);

        $this->assertEqual($set->getRelationENV(), array(
            "info" => $info,
            "value" => 1
        ));

        $this->assertNotEqual($e->ToArray(), $oldE->ToArray());
        $this->assertNotEqual($newE->ToArray(), $oldE->toArray());
        $this->assertEqual($set->first()->blubid, 1);
        $this->assertEqual($newE->blubid, 1);
        $this->assertEqual($e->blubid, 1);

        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $this->assertEqual($set->first()->blubid, 1);
    }

    /**
     * tests if create model is assigning the field correctly.
     */
    public function createNewModel() {
        $set = new HasMany_DataObjectSet(MockWriteEntity::class);
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->setRelationENV($info = new ModelHasManyRelationShipInfo(MockWriteEntity::class, "blah", array(
            "target" => User::class,
            "inverse"   => "blub",
            "validatedInverse"  => true
        )), 1, new MockWriteEntity());

        $this->assertEqual($set->createNewModel()->blahid, 1);
    }

    /**
     * tests if loop with one only have one element.
     */
    public function testLoopOneElement() {
        $set = new HasMany_DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->add($this->patrick);

        $i = 0;
        foreach($set as $item) {
            $this->assertEqual($this->patrick->name, $item->name);
            $i++;
        }
        $this->assertEqual(1, $i);
    }

    /**
     *
     */
    public function testInitHasManyMock() {
        $hasMany = new MockHasManyClass();
        $this->assertInstanceOf(MockHasManyClass::class, $hasMany);
        $this->assertInstanceOf(HasMany_DataObjectSet::class, $hasMany->many());
    }

    /**
     *
     */
    public function testLoopHasManyMock() {
        $hasMany = new MockHasManyClass();
        $this->assertInstanceOf(MockHasManyClass::class, $hasMany);
        $this->assertInstanceOf(HasMany_DataObjectSet::class, $hasMany->many());

        $hasMany->many()->add(new MockHasOneClass());

        $i = 0;
        foreach($hasMany->many() as $one) {
            $i++;
        }
        $this->assertEqual(1, $i);
        $this->assertEqual(1, $hasMany->many()->count());
    }

    /**
     *
     */
    public function testCountHasManyMock() {
        $hasMany = new MockHasManyClass();
        $this->assertInstanceOf(MockHasManyClass::class, $hasMany);
        $this->assertInstanceOf(HasMany_DataObjectSet::class, $hasMany->many());

        $hasMany->many()->add(new MockHasOneClass());
        $this->assertEqual(1, $hasMany->many()->count());
    }

    /**
     *
     */
    public function testCountMultiHasManyMock() {
        $hasMany = new MockHasManyClass();
        $this->assertInstanceOf(MockHasManyClass::class, $hasMany);
        $this->assertInstanceOf(HasMany_DataObjectSet::class, $hasMany->many());

        $a = 5;
        for($i = 0; $i < $a; $i++) {
            $hasMany->many()->add(new MockHasOneClass());
        }
        $this->assertEqual($a, $hasMany->many()->count());
    }

    /**
     *
     */
    public function testCreateHasManyAndCheckInverse() {
        $hasMany = new MockHasManyClass();
        $this->assertInstanceOf(MockHasManyClass::class, $hasMany);
        $this->assertInstanceOf(HasMany_DataObjectSet::class, $hasMany->many());

        $a = 5;
        for($i = 0; $i < $a; $i++) {
            $hasMany->many()->add(new MockHasOneClass());
        }

        /** @var MockHasOneClass $one */
        foreach($hasMany->many() as $one) {
            $this->assertEqual($one->one, $hasMany);
        }
    }

    /**
     *
     */
    public function testCreateHasManyAndWrite() {
        try {
            $hasMany = new MockHasManyClass();
            $this->assertInstanceOf(MockHasManyClass::class, $hasMany);
            $this->assertInstanceOf(HasMany_DataObjectSet::class, $hasMany->many());

            $a = 5;
            for ($i = 0; $i < $a; $i++) {
                $hasMany->many()->add(new MockHasOneClass());
            }

            $hasMany->writeToDB(false, true);

            /** @var MockHasManyClass $hasManyFromDB */
            $hasManyFromDB = \DataObject::get_one(MockHasManyClass::class, array("id" => $hasMany->id));
            $this->assertEqual($a, $hasManyFromDB->many()->count());

            $this->assertEqual($hasManyFromDB, $hasManyFromDB->many()->first()->one);

            /** @var MockHasOneClass $one */
            foreach($hasManyFromDB->many() as $one) {
                $this->assertEqual($hasManyFromDB, $one->one);
            }
        } finally {
            if($hasMany) {
                foreach($hasMany->many() as $one) {
                    $one->remove(true);
                }

                $hasMany->remove(true);
            }
        }
    }
    
     /**
     *
     */
    public function testCreateHasManyAndWriteRemove() {
        try {
            $hasMany = new MockHasManyClass();
            $this->assertInstanceOf(MockHasManyClass::class, $hasMany);
            $this->assertInstanceOf(HasMany_DataObjectSet::class, $hasMany->many());

            $a = 5;
            for ($i = 0; $i < $a; $i++) {
                $hasMany->many()->add(new MockHasOneClass());
            }

            $hasMany->writeToDB(false, true);

            /** @var MockHasManyClass $hasManyFromDB */
            $hasManyFromDB = \DataObject::get_one(MockHasManyClass::class, array("id" => $hasMany->id));
            $this->assertEqual($a, $hasManyFromDB->many()->count());
            
            $removed = null;
            /** @var MockHasOneClass $one */
            foreach($hasManyFromDB->many() as $one) {
                $this->assertEqual($hasManyFromDB, $one->one);
                if(!$removed) {
                    $removed = $one;
                    $removed->override = true;
                    $hasManyFromDB->many()->removeFromSet($one);
                }
            }
            $hasManyFromDB->many()->commitStaging(false, true);
            
            $this->assertEqual($a - 1, $hasManyFromDB->many()->count());
            $this->assertNull($removed->one);
        } finally {
            if($hasMany) {
                foreach($hasMany->many() as $one) {
                    $one->remove(true);
                }

                $hasMany->remove(true);
            }
            if($removed) {
                $removed->remove(true);
            }
        }
    }

    public function testHasManyGetConverted() {
        try {
            $hasMany = new MockHasManyClass();
            $this->assertInstanceOf(MockHasManyClass::class, $hasMany);
            $this->assertInstanceOf(HasMany_DataObjectSet::class, $hasMany->many());

            $a = 5;
            for ($i = 0; $i < $a; $i++) {
                $hasMany->many()->add(new MockHasOneClass());
            }

            $hasMany->writeToDB(false, true);

            /** @var MockHasManyClass $hasManyFromDB */
            $hasManyFromDB = \DataObject::get_one(MockHasManyClass::class, array("id" => $hasMany->id));
            $this->assertEqual($a, $hasManyFromDB->many()->count());

            $hasManyFromDB->many()->first()->one = clone $hasManyFromDB->many()->first()->one;
            $hasManyFromDB->many()->first()->one->blah = 1;

            $i = 0;
            /** @var MockHasOneClass $one */
            foreach($hasManyFromDB->many() as $one) {
                if($i == 0) {
                    $hasManyFromDBWithBlah = clone $hasManyFromDB;
                    $hasManyFromDBWithBlah->blah = 1;
                    $this->assertEqual(1, $one->one->blah);
                    $this->assertEqual($hasManyFromDBWithBlah, $one->one);
                    $i++;
                } else {
                    $this->assertNull($one->one->blah);
                    $this->assertEqual($hasManyFromDB, $one->one);
                }
            }
        } finally {
            if($hasMany) {
                foreach($hasMany->many() as $one) {
                    $one->remove(true);
                }

                $hasMany->remove(true);
            }
        }
    }
}

/**
 * Class MockHasOneClass
 * @property MockHasManyClass one
 * @package Goma\Test\Model
 */
class MockHasOneClass extends \DataObject {
    static $has_one = array(
        "one" => MockHasManyClass::class
    );

    /**
     * checks if one is set.
     *
     * @param \ModelWriter $modelWriter
     * @throws \FormInvalidDataException
     */
    public function onBeforeWrite($modelWriter)
    {
        parent::onBeforeWrite($modelWriter);

        if(!$this->one && !$this->override) {
            throw new \FormInvalidDataException("one");
        }
    }
}

/**
 * Class MockHasManyClass
 * @property MockHasManyClass one
 * @package Goma\Test\Model
 * @method HasMany_DataObjectSet many($filter = null, $sort = null)
 */
class MockHasManyClass extends \DataObject {
    static $has_many = array(
        "many"  => MockHasOneClass::class
    );
}
