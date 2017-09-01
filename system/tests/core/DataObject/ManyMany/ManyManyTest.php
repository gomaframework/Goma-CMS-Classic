<?php defined("IN_GOMA") OR die();
/**
 * Integration-Tests for DataObject-ManyMany-Implementation.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class ManyManyIntegrationTest extends GomaUnitTest implements TestAble
{
    /**
     * area
     */
    static $area = "ManyMany";

    /**
     * internal name.
     */
    public $name = "ManyManyIntegrationTest";

    /**
     * @var ManyManyTestObjectOne[]
     */
    protected   $ones = array();

    /**
     * @var ManyManyTestObjectTwo[]
     */
    protected   $twos = array(),
                $createdSet = 0;

    public function setUp()
    {
        foreach(DBTableManager::Tables(ManyManyTestObjectOne::class) as $table) {
            if(!SQL::query("TRUNCATE TABLE " . DB_PREFIX . $table)) {
                throw new MySQLException();
            }
        }

        foreach(DBTableManager::Tables(ManyManyTestObjectTwo::class) as $table) {
            if(!SQL::query("TRUNCATE TABLE " . DB_PREFIX . $table)) {
                throw new MySQLException();
            }
        }

        /** @var ModelManyManyRelationShipInfo $relationship */
        foreach(gObject::instance(ManyManyTestObjectTwo::class)->ManyManyRelationships() as $relationship) {
            if(!SQL::query("TRUNCATE TABLE " . DB_PREFIX . $relationship->getTableName())) {
                throw new MySQLException();
            }
        }

        foreach(DBTableManager::Tables(ManyManyBiDirObj::class) as $table) {
            if(!SQL::query("TRUNCATE TABLE " . DB_PREFIX . $table)) {
                throw new MySQLException();
            }
        }

        /** @var ModelManyManyRelationShipInfo $relationship */
        foreach(gObject::instance(ManyManyBiDirObj::class)->ManyManyRelationships() as $relationship) {
            if(!SQL::query("TRUNCATE TABLE " . DB_PREFIX . $relationship->getTableName())) {
                throw new MySQLException();
            }
        }

        $this->ones = array();
        $this->twos = array();
        $this->createdSet = 0;

        for($i = 0; $i < 5; $i++) {
            $this->ones[$i] = $one = new ManyManyTestObjectOne(array(
                "one" => "one_" . $i
            ));
            $one->writeToDB(true, true);
            $one->random = randomString(10);
            $one->writeToDB(false, true);

            $this->twos[$i] = $two = new ManyManyTestObjectTwo(array(
                "two"   => "two_" . $i
            ));
            $two->writeToDB(true, true);
            $two->random = randomString(10);
            $two->writeToDB(false, true);

            /** @var ManyMany_DataObjectSet $onesInTwo */
            $onesInTwo = $two->ones();
            for($a = $i; $a >= 0; $a--) {
                $this->ones[$a]->extra = $i . "_" . $a;
                $onesInTwo->add($this->ones[$a]);
                $this->createdSet++;
            }

            $onesInTwo->commitStaging(false, true);
        }

        for($i = 0; $i < 5; $i++) {
            $biDir = new ManyManyBiDirObj(array(
                "number" => $i,
                "random" => randomString(10)
            ));
            $biDir->writeToDB(false, true);
        }
    }

    public function testManyManyInitGetter() {
        $data = DataObject::get("ManyManyTestObjectTwo")->getRange(0, 3)->fieldToArray("versionid");
        $this->assertEqual(3, count($data));
        $object = new ManyManyTestObjectOne(array(
            "twosids" => $data
        ));
        $this->assertEqual($data, $object->twosids);
        $this->assertEqual(3, $object->twos()->count());
    }

    public function testLoad() {
        /** @var ManyManyTestObjectOne $firstOne */
        $firstOne = DataObject::get_one("ManyManyTestObjectOne");

        $set = $firstOne->twos();
        $data = $set->getRelationshipData();
        $cloned = clone $firstOne;
        $cloned->writeToDB(false, true);
        $set->setRelationENV($firstOne->getManyManyInfo("twos"), $cloned);

        $data2 = $set->getRelationshipData();
        $this->assertEqual(count($data2), count($data));
    }

    /**
     * tests basic data loading.
     */
    public function testDataLoading() {

        $countedSet = 0;
        /** @var ManyManyTestObjectTwo $two */
        foreach(DataObject::get("ManyManyTestObjectTwo") as $two) {
            $ones = $two->ones();
            $twoInt = (int) str_replace("two_", "", $two->two);
            $this->assertEqual($ones->count(), $twoInt + 1);

            /** @var ManyManyTestObjectOne $one */
            foreach($ones as $one) {
                $oneInt = (int) str_replace("one_", "", $one->one);
                $this->assertEqual($one->extra, $twoInt . "_" . $oneInt);
                $countedSet++;
            }
        }

        $countedSetB = 0;

        /** @var ManyManyTestObjectOne $one */
        foreach(DataObject::get("ManyManyTestObjectOne") as $one) {
            $twos = $one->twos();
            $oneInt = (int) str_replace("one_", "", $one->one);
            $this->assertEqual($twos->count(), 5 - $oneInt);

            /** @var ManyManyTestObjectTwo $two */
            foreach($twos as $two) {
                $twoInt = (int) str_replace("two_", "", $two->two);
                $this->assertEqual($two->extra, $twoInt . "_" . $oneInt);
                $countedSetB++;
            }
        }

        $this->assertEqual($countedSet, $this->createdSet);
        $this->assertEqual($countedSetB, $this->createdSet);
    }

    /**
     * tests if ids-setter is working as expected
     *
     * 1. gets relationship
     * 2. splice relationship down to 3
     * 3. rewrite relationship
     * 4. check if written correctly
     * 5. shuffle relationship
     * 6. write
     * 7. check if written correctly
     */
    public function testSetIds() {
        /** @var ManyManyTestObjectOne $firstOne */
        $firstOne = DataObject::get_one("ManyManyTestObjectOne");

        $this->assertEqual($firstOne->twos()->count(), count($this->twos));
        $this->assertEqual(count($firstOne->twosids), count($this->twos));

        $ids = $firstOne->twosids;
        array_splice($ids, 3);
        $firstOne->twosids = $ids;
        $this->assertEqual(count($firstOne->twosids), 3);

        /** @var ManyManyTestObjectTwo $two */
        $i = 0;
        foreach($firstOne->twos() as $two) {
            $this->assertEqual($two->versionid, $ids[$i]);
            $i++;
        }

        shuffle($ids);
        $firstOne->twosids = $ids;
        $i = 0;
        foreach($firstOne->twos() as $two) {
            $this->assertEqual($two->versionid, $ids[$i]);
            $i++;
        }
    }

    /**
     * @throws DataObjectSetCommitException
     */
    public function testRewriteRelationship() {
        /** @var ManyManyTestObjectOne $firstOne */
        $firstOne = DataObject::get_one("ManyManyTestObjectOne");

        $this->assertEqual($firstOne->twos()->count(), count($this->twos));
        $ids = $firstOne->twosids;
        array_splice($ids, 4);
        shuffle($ids);
        $firstOne->twosids = $ids;
        $firstOne->twos()->commitStaging(false, true, 3);

        $newFirstOne = DataObject::get_one("ManyManyTestObjectOne");
        $this->assertEqual($newFirstOne->twos()->count(), 4);
        /** @var ManyManyTestObjectTwo $two */
        $i = 0;
        foreach($newFirstOne->twos() as $two) {
            $this->assertEqual($two->versionid, $ids[$i]);
            $i++;
        }
    }

    /**
     * tests if sort is working correctly
     *
     * 1. gets one object
     * 2. checks if setup() initiliazed correctly
     * 3. shuffles manymany-relationship
     * 4. checks if shuffle has been completed
     * 5. writes new object
     * 6. checks if object has been correctly written
     *
     * @throws DataObjectSetCommitException
     */
    public function testSort() {
        /** @var ManyManyTestObjectOne $firstOne */
        $firstOne = DataObject::get_one("ManyManyTestObjectOne");

        $this->assertEqual($firstOne->twos()->count(), count($this->twos));
        $ids = $firstOne->twosids;

        shuffle($ids);
        $twos = $firstOne->twos();
        $recordids = array();
        foreach($ids as $id) {
            $recordids[] = $twos->find("versionid", $id)->recordid;
        }
        $twos->setSortByIdArray($recordids);

        $this->assertEqual($twos->getRelationshipIDs(), $ids);

        $twos->commitStaging(false, true);
        $firstOne = DataObject::get_one("ManyManyTestObjectOne");
        $this->assertEqual($firstOne->twosids, $ids);
    }

    /**
     * tests if filter API is working as expected
     */
    public function testFilter() {
        $firstOne = DataObject::get_one("ManyManyTestObjectOne");

        $this->assertEqual($firstOne->twos(array("two" => $this->twos[0]->two))->count(), 1);
        $this->assertEqual($firstOne->twos()->filter(array("two" => $this->twos[0]->two))->count(), 1);
    }

    /**
     * tests state-upgrades when writing manymany-DataObjectSets from a DataObject
     * it tests just state records to published
     *
     * 1. creates test objects (one one and two twos)
     * 2. assigns the twos to the one
     * 3. write state of one
     * 4. checks if data is available
     * 5. checks if twos have NOT been published
     * 6. publishes complete object-set
     * 7. checks if everything has been published
     * 8. cleanup
     *
     * @throws MySQLException
     */
    public function testUpgradeStateToPublish() {
        $newOne = new ManyManyTestObjectOne(array(
            "one" => 10
        ));
        $newOne->twos()->add(new ManyManyTestObjectTwo(array(
            "two" => 10
        )));
        $newOne->twos()->add(new ManyManyTestObjectTwo(array(
            "two" => 11
        )));
        $newOne->writeToDB(true, true, 1);

        $this->assertEqual($newOne, $newOne->twos()->getOwnRecord());
        $this->assertEqual(2, $newOne->twos()->count());

        /** @var ManyManyTestObjectOne $stateOne */
        $stateOne = DataObject::get_versioned(ManyManyTestObjectOne::class, DataObject::VERSION_STATE, array(
            "one" => 10
        ))->first();
        $this->assertEqual(DataObject::VERSION_STATE, $stateOne->queryVersion);

        $this->assertIsA($stateOne, ManyManyTestObjectOne::class);
        $this->assertEqual(2, $stateOne->twos()->count());
        $this->assertEqual(0, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(10, 11)
        ))->count());

        $stateOne->writeToDB(false, true, 2);
        $this->assertEqual(2, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(10, 11)
        ))->count());

        $this->assertEqual(2, $stateOne->twos()->count());
        foreach($stateOne->twos() as $two) {
            $two->remove(true);
        }

        $this->assertEqual(0, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(10, 11)
        ))->count());

        $stateOne->remove(true);
        $this->assertNull(DataObject::get_versioned(ManyManyTestObjectOne::class, DataObject::VERSION_STATE, array(
            "one" => 10
        ))->first());
    }

    /**
     * tests state-upgrades when writing manymany-DataObjectSets from a DataObject
     * it tests when also already published records are available, which has a new state-version
     *
     * 1. Create One TestObjectOne and two testObjectTwo.
     * 2. Assign both of the twos to the one.
     * 3. edit the first two and publish it
     * 4. edit the first two and make a draft
     * 5. assert correct records are available for one
     * 6. checks if record in published version has right data.
     * 7. publishes manymany-relationship
     * 8. checks if everything got right
     * 9. removes one and checks if remove was successful
     *
     * @throws MySQLException
     */
    public function testUpgradeStateToPublishWithPublished()
    {
        $newOne = new ManyManyTestObjectOne(array(
            "one" => 10
        ));
        $newOne->twos()->add($first = new ManyManyTestObjectTwo(array(
            "two" => 10
        )));
        $newOne->twos()->add(new ManyManyTestObjectTwo(array(
            "two" => 11
        )));
        $first->writeToDB(true, true);
        $first->two = 12;
        $first->writeToDB(false, true, 1);
        $newOne->writeToDB(true, true, 1);

        $this->assertEqual($newOne, $newOne->twos()->getOwnRecord());
        $this->assertEqual(12, $newOne->twos()->first()->two);
        $this->assertEqual(2, $newOne->twos()->count());

        $this->assertEqual(1, DataObject::count(ManyManyTestObjectTwo::class, array(
            "two" => 10
        )));

        /** @var ManyManyTestObjectOne $stateOne */
        $stateOne = DataObject::get_versioned(ManyManyTestObjectOne::class, DataObject::VERSION_STATE, array(
            "one" => 10
        ))->first();
        $this->assertEqual(DataObject::VERSION_STATE, $stateOne->queryVersion);

        $this->assertIsA($stateOne, ManyManyTestObjectOne::class);
        $this->assertEqual(2, $stateOne->twos()->count());
        $this->assertEqual(1, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(10, 11)
        ))->count());

        // publish
        $stateOne->writeToDB(false, true, 2);
        $this->assertEqual(1, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(10, 11)
        ))->count());
        $this->assertEqual(2, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(12, 11)
        ))->count());

        $this->assertEqual(2, $stateOne->twos()->count());

        // cleanup
        foreach ($stateOne->twos() as $two) {
            $two->remove(true);
        }

        $this->assertEqual(0, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(10, 11)
        ))->count());

        $stateOne->remove(true);
        $this->assertNull(DataObject::get_versioned(ManyManyTestObjectOne::class, DataObject::VERSION_STATE, array(
            "one" => 10
        ))->first());
    }

    /**
     * tests state-reverts.
     * it tests when also already published records are available, which has a new state-version
     *
     * 1. Create One TestObjectOne and two testObjectTwo.
     * 2. Assign both of the twos to the one.
     * 3. edit the first two and publish it
     * 4. edit the first two and make a draft
     * 5. assert correct records are available for one
     * 6. checks if record in published version has right data.
     * 7. publishes manymany-relationship
     * 8. checks if everything got right
     * 9. removes one and checks if remove was successful
     *
     * @throws MySQLException
     */
    public function testRevertStateToPublishWithPublished()
    {
        $newOne = new ManyManyTestObjectOne(array(
            "one" => 10
        ));
        $newOne->twos()->add(new ManyManyTestObjectTwo(array(
            "two" => 10
        )));
        $newOne->twos()->add(new ManyManyTestObjectTwo(array(
            "two" => 11
        )));
        $newOne->writeToDB(true, true, 2);

        $this->assertEqual(array(10, 11), $newOne->twos()->fieldToArray("two"));
        $first = $newOne->twos()->first();
        $first->two = 12;
        $newOne->twos()->updateFields(
            $first
        );
        $newOne->writeToDB(false, true, 1);

        $this->assertEqual($newOne, $newOne->twos()->getOwnRecord());
        $this->assertEqual(12, $newOne->twos()->first()->two);
        $this->assertEqual(2, $newOne->twos()->count());

        $this->assertEqual(1, DataObject::count(ManyManyTestObjectTwo::class, array(
            "two" => 10
        )));
        $this->assertEqual(array(12, 11), $newOne->twos()->fieldToArray("two"));

        // check for state
        $stateOne = DataObject::get_versioned(ManyManyTestObjectOne::class, DataObject::VERSION_STATE, array(
            "one" => 10
        ))->first();
        $this->assertEqual(array(12, 11), $stateOne->twos()->fieldToArray("two"));

        // revert
        /** @var ManyManyTestObjectOne $stateOne */
        $publishedOne = DataObject::get(ManyManyTestObjectOne::class, array(
            "one" => 10
        ))->first();
        $this->assertEqual(array(10, 11), $publishedOne->twos()->fieldToArray("two"));

        $publishedOne->writeToDB(false, true, 2);

        // check for revert
        $this->assertEqual(2, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(10, 11)
        ))->count());
        $this->assertEqual(1, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(12, 11)
        ))->count());

        // check for state
        $stateOne = DataObject::get_versioned(ManyManyTestObjectOne::class, DataObject::VERSION_STATE, array(
            "one" => 10
        ))->first();
        $this->assertEqual(array(10, 11), $stateOne->twos()->fieldToArray("two"));

        // cleanup
        $this->assertEqual(2, $stateOne->twos()->count());
        foreach ($stateOne->twos() as $two) {
            $two->remove(true);
        }

        $this->assertEqual(0, DataObject::get(ManyManyTestObjectTwo::class, array(
            "two" => array(10, 11)
        ))->count());

        $stateOne->remove(true);
        $this->assertNull(DataObject::get_versioned(ManyManyTestObjectOne::class, DataObject::VERSION_STATE, array(
            "one" => 10
        ))->first());
    }

    /**
     * tests Bidirectional Relationships, due to the fact that it is referencing itself.
     *
     * 1. Create two objects
     * 2. Add second to first
     * 3. Check if written correctly
     *
     * @throws DataObjectSetCommitException
     */
    public function testBiDir() {
        $this->assertEqual(5, DataObject::get(ManyManyBiDirObj::class)->count());

        /** @var ManyManyBiDirObj $zero */
        $zero = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 0));
        /** @var ManyManyBiDirObj $one */
        $one = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 1));

        $zero->my()->add($one);
        $zero->my()->commitStaging(false, true);

        $this->assertEqual(1, $zero->my()->count());

        $this->assertEqual(1, $one->my()->count());
        $one->my()->removeFromSet($zero);
        $one->my()->commitStaging(false, true);

        $zero = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 0));
        $this->assertEqual(0, $zero->my()->count());
    }

    /**
     * tests if editing bidirectional works
     *
     * @group ManyManyBiDirectional
     * @throws DataObjectSetCommitException
     */
    public function testBiDirEdit() {
        $this->assertEqual(5, DataObject::get(ManyManyBiDirObj::class)->count());

        /** @var ManyManyBiDirObj $zero */
        $two = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 2));
        /** @var ManyManyBiDirObj $one */
        $one = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 1));

        /** @var ManyManyBiDirObj $three */
        $three = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 3));

        $one->my()->add($two);
        $one->my()->commitStaging(false, true);

        $this->assertEqual(1, $one->my()->count());

        $this->assertEqual(1, $two->my()->count());

        $one = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 1));
        $one->my()->add($three);
        $one->my()->commitStaging(false, true);

        $this->assertEqual(2, $one->my()->count());

        $one = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 1));
        $one->my()->removeFromSet($two);
        $one->my()->commitStaging(false, true);

        $this->assertEqual(1, $one->my()->count());
        $this->assertEqual($three->id, $one->my()->first()->id);

        $one->my()->removeFromSet($three);
        $one->my()->commitStaging(false, true);

        $this->assertEqual(null, $one->my()->first());
    }

    /**
     * tests if initing bidirectional from ids.
     */
    public function testBiDirectionInitFromIds() {
        $this->assertEqual(5, DataObject::get(ManyManyBiDirObj::class)->count());

        /** @var ManyManyBiDirObj $zero */
        $two = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 2));
        /** @var ManyManyBiDirObj $one */
        $one = DataObject::get_one(ManyManyBiDirObj::class, array("number" => 1));

        $one->myids = array($two->versionid);
        $one->my()->commitStaging(false, true);

        $this->assertEqual($two->id, $one->my()->first()->id);

        $one->myids = array();
        $one->writeToDB(false, true);
        $this->assertNull($one->my()->first());
    }
 
    /**
     * tests if class-info is correctly initialized for bidir.
     */
    public function testBiDirClassInfo() {
        $this->assertTrue(isset(ClassInfo::$class_info["manymanybidirobj"]["many_many_relations"]["my"]));
        $this->assertFalse(isset(ClassInfo::$class_info["manymanybidirobj"]["many_many_relations_extra"]));
    }

    /**
     * tests if exception is raised when committing data when source part of
     * ManyMany-Relationship is a trasient object.
     */
    public function testExceptionWhenCommitingOnNotWrittenObject() {
        $transient = new ManyManyTestObjectOne();
        $transient->twos()->add(DataObject::get_one(ManyManyTestObjectTwo::class));
        $this->assertEqual(1, $transient->twos()->count());
        $this->assertThrows(function() use($transient) {
            $transient->twos()->commitStaging(false, true);
        }, "LogicException");
    }

    /**
     * @throws DataObjectSetCommitException
     */
    public function testSortByManyManyWithManyManyData()
    {
        /** @var ManyManyTestObjectOne $firstOne */
        $firstOne = DataObject::get_one("ManyManyTestObjectOne");

        $this->assertNotNull($firstOne->twos()->sort("extra", "DESC")->first());

        $this->assertEqual($firstOne->twos()->count(), count($this->twos));
        $ids = $firstOne->twosids;

        $firstOne->twosids = array($ids[0], $ids[1]);

        $this->assertNotNull($firstOne->twos()->sort("extra", "DESC")->first());
    }

    /**
     * tests if ManyMany Fields casting is casted also at reading a value.
     */
    public function testCastingOnReadManyMany() {
        $one = DataObject::get_one(ManyManyTestObjectOne::class);
        $two = $one->twos()->first();
        
        $this->assertNotNull($two);
        $this->assertIsA($two->extraCasted(), MockStringCasting::class);
        $this->assertEqual(21, $two->extraCasted()->raw());
    }
}

/**
 * Class ManyManyTestObjectOne
 *
 * @method ManyMany_DataObjectSet twos()
 * @property array twosids
 * @property string one
 * @property string extra
 * @property string random
 */
class ManyManyTestObjectOne extends DataObject {
    static $versions = true;

    static $db = array(
        "one"       => "varchar(200)",
        "random"    => "varchar(200)"
    );

    static $many_many = array(
        "twos"  => "ManyManyTestObjectTwo"
    );

    static $search_fields = false;

    static $many_many_extra_fields = array(
        "twos"  => array(
            "extra"         => "varchar(100)",
            "extraCasted"   => "MockStringCasting"
        )
    );
}

/**
 * Class ManyManyTestObjectTwo
 *
 * @method ManyMany_DataObjectSet ones()
 * @property array onesids
 * @property string two
 * @property string extra
 * @property string random
 */
class ManyManyTestObjectTwo extends DataObject {

    static $versions = true;

    static $db = array(
        "two"   => "varchar(200)",
        "random"    => "varchar(200)"
    );

    static $search_fields = false;

    static $belongs_many_many = array(
        "ones"  => array(
            DataObject::RELATION_TARGET     => "ManyManyTestObjectOne",
            DataObject::RELATION_INVERSE    => "twos"
        )
    );
}

class MockStringCasting extends DBField {
    /**
     * gets the field-type
     *
     * @param array $args
     * @return string
     */
    static public function getFieldType($args = array()) {
        return "text";
    }

    public function raw() {
        return 21;
    }
}

/**
 * Class ManyManyTestObjectTwo
 *
 * @method ManyMany_DataObjectSet my()
 */
class ManyManyBiDirObj extends DataObject {

    static $versions = true;

    static $db = array(
        "number"    => "int(10)",
        "random"    => "varchar(200)"
    );

    static $search_fields = false;

    static $many_many = array(
        "my" => ManyManyBiDirObj::class
    );
}
