<?php use Goma\Test\Model\DumpDBElementPerson;

defined("IN_GOMA") OR die();
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


    /**
     * @var DumpDBElementPerson
     */
    protected $daniel;

    /**
     * @var DumpDBElementPerson
     */
    protected $kathi;

    /**
     * @var DumpDBElementPerson
     */
    protected $patrick;

    /**
     * @var DumpDBElementPerson
     */
    protected $janine;

    /**
     * @var DumpDBElementPerson
     */
    protected $nik;

    /**
     * @var DumpDBElementPerson
     */
    protected $julian;

    /**
     * @var DumpDBElementPerson
     */
    protected $fabian;

    /**
     * @var DumpDBElementPerson
     */
    protected $franz;

    /**
     * @var DumpDBElementPerson
     */
    protected $lisa;

    /**
     * @var DumpDBElementPerson
     */
    protected $julia;

    /**
     * @var DumpDBElementPerson
     */
    protected $jenny;

    /**
     * @var DumpDBElementPerson[]
     */
    protected $allPersons;

    public function setUp()
    {
        $this->daniel =  new DumpDBElementPerson("Daniel", 20, "M");
        $this->kathi = new DumpDBElementPerson("Kathi", 22, "W");
        $this->patrick = new DumpDBElementPerson("Patrick", 16, "M");
        $this->janine = new DumpDBElementPerson("Janine", 19, "W");
        $this->nik = new DumpDBElementPerson("Nik", 21, "M");
        $this->julian = new DumpDBElementPerson("Julian", 20, "M");
        $this->fabian = new DumpDBElementPerson("Fabian", 22, "M");
        $this->franz = new DumpDBElementPerson("Franz", 56, "M");
        $this->lisa = new DumpDBElementPerson("Lisa", 18, "W");
        $this->julia = new DumpDBElementPerson("Julia", 25, "W");
        $this->jenny = new DumpDBElementPerson("Jenny", 35, "W");

        $this->allPersons = array($this->daniel, $this->kathi, $this->patrick, $this->nik,
                                  $this->julian, $this->janine, $this->fabian, $this->franz,
                                  $this->lisa, $this->julia, $this->jenny);


        foreach($this->allPersons as $person) {
            $person->queryVersion = DataObject::VERSION_PUBLISHED;
        }
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

    public function testSortBig() {
        $set = new ManyMany_DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        foreach($this->allPersons as $person) {
            $set->add($person);
        }

        $this->assertEqual(count($this->allPersons), $set->count());
        $this->assertEqual(count($this->allPersons), count($set->ToArray()));

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

    public function testRemoveInLoop() {
        $set = new ManyMany_DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $this->assertTrue(count($this->allPersons) > 10);

        foreach($this->allPersons as $person) {
            $set->add($person);
        }

        $this->assertEqual(count($this->allPersons), $set->count());
        $this->assertEqual(count($this->allPersons), count($set->ToArray()));

        $i = 0;
        foreach($set as $record) {
            $this->assertEqual($this->allPersons[$i], $record);
            if($i == 3) {
                $set->removeFromStage($record);
            }
            $i++;
        }
        $this->assertEqual(count($this->allPersons), $i);
        $this->assertEqual(count($this->allPersons) - 1, $set->count());
        $this->assertEqual(count($this->allPersons) - 1, count($set->ToArray()));
    }

    public function testRemoveFromSetInLoop() {
        $set = new ManyMany_DataObjectSet();
        $set->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);

        $this->assertTrue(count($this->allPersons) > 10);

        foreach($this->allPersons as $person) {
            $set->add($person);
        }

        $this->assertEqual(count($this->allPersons), $set->count());
        $this->assertEqual(count($this->allPersons), count($set->ToArray()));

        $i = 0;
        foreach($set as $record) {
            $this->assertEqual($this->allPersons[$i], $record);
            if($i == 3) {
                $set->removeFromSet($record);
            }
            $i++;
        }
        $this->assertEqual(count($this->allPersons), $i);
        $this->assertEqual(count($this->allPersons) - 1, $set->count());
        $this->assertEqual(count($this->allPersons) - 1, count($set->ToArray()));
    }
}
