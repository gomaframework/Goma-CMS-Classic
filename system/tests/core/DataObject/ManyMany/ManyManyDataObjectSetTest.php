<?php defined("IN_GOMA") OR die();
/**
 * Tests for DataObjectSet for ManyMany
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class ManyManyDataObjectSet extends GomaUnitTest implements TestAble {
    /**
     * area
     */
    static $area = "ManyMany";

    /**
     * internal name.
     */
    public $name = "ManyManyDataObjectSet";

    protected $daniel;
    protected $kathi;
    protected $patrick;
    protected $janine;
    protected $nik;
    protected $julian;

    public function setUp()
    {
        $this->daniel =  new DumpDBElementPerson("Daniel", 20, "M");
        $this->kathi = new DumpDBElementPerson("Kathi", 22, "W");
        $this->patrick = new DumpDBElementPerson("Patrick", 16, "M");
        $this->janine = new DumpDBElementPerson("Janine", 19, "W");
        $this->nik = new DumpDBElementPerson("Nik", 21, "M");
        $this->julian = new DumpDBElementPerson("Julian", 20, "M");

        $this->daniel->queryVersion = $this->kathi->queryVersion = $this->patrick->queryVersion = $this->janine->queryVersion =
        $this->nik->queryVersion = $this->julian->queryVersion = DataObject::VERSION_PUBLISHED;
    }

    /**
     * test filter.
     */
    public function testFilter() {
        $user = new User();
        $relationShip = $user->getManyManyInfo("groups");

        $getRecordIdMethod = new ReflectionMethod("ManyMany_DataObjectSet", "getRecordIdQuery");
        $getRecordIdMethod->setAccessible(true);

        $set = new ManyMany_DataObjectSet();
        $set->setRelationENV($relationShip, $user);

        $this->assertEqual($set->getFilterForQuery(), array(" " . $relationShip->getTargetBaseTableName() . ".recordid IN (".$getRecordIdMethod->invoke($set)->build("distinct recordid").") "));

        $set->setSourceData(array(
            1, 2, 3
        ));

        $recordidQuery = new SelectQuery($relationShip->getTargetBaseTableName(), "", array(
            "id" => array(1,2,3)
        ));
        $this->assertPattern("/".preg_quote($recordidQuery->build("distinct recordid"), "/")."/", $set->getFilterForQuery()[0]);

        $filter1 = array("name" => "blub");
        $set->filter($filter1);

        $this->assertEqual($set->getFilterForQuery()["name"], "blub");
        $this->assertPattern("/".preg_quote($recordidQuery->build("distinct recordid"), "/")."/", $set->getFilterForQuery()[0]);

        $set->filter("name = 'blub'");
        $this->assertEqual("name = 'blub'", $set->getFilterForQuery()[0]);
        $this->assertPattern("/".preg_quote($recordidQuery->build("distinct recordid"), "/")."/", $set->getFilterForQuery()[1]);
    }

    public function testEmpty() {
        $set = new ManyMany_DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $this->assertEqual(0, $set->count());
        $this->assertEqual(array(), $set->ToArray());
    }

    public function testSort() {
        $set = new ManyMany_DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $set->add($this->janine);
        $set->add($this->nik);
        $set->add($this->daniel);

        $this->assertEqual(3, $set->count());
        $this->assertEqual(3, count($set->ToArray()));

        $set->sortCallback(function($a, $b) {
            if($a->age == $b->age) {
                return 0;
            }

            return $a->age > $b->age ? 1 : -1;
        });

        $firstAge = $set[0]->age;
        foreach($set as $current) {
            $this->assertTrue($current->age >= $firstAge);
            $firstAge = $current->age;
        }
    }
}
