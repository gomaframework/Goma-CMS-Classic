<?php defined("IN_GOMA") OR die();

/**
 * Unit-Tests for DataObject-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DataSetTests extends GomaUnitTest {
    static $area = "NModel";
    /**
     * name
     */
    public $name = "DataSet";

    protected $daniel;
    protected $kathi;
    protected $patrick;
    protected $janine;
    protected $nik;
    protected $julian;

    public function setUp()
    {
        $this->daniel =  new DumpElementPerson("Daniel", 20, "M");
        $this->kathi = new DumpElementPerson("Kathi", 22, "W");
        $this->patrick = new DumpElementPerson("Patrick", 16, "M");
        $this->janine = new DumpElementPerson("Janine", 19, "W");
        $this->nik = new DumpElementPerson("Nik", 21, "M");
        $this->julian = new DumpElementPerson("Julian", 20, "M");
    }

    public function testCreate() {
        $list = new DataSet();

        $this->assertEqual($list->count(), 0);
        $this->assertEqual(count($list), 0);
        $this->assertEqual($list->DataClass(), null);
        $this->assertEqual($list->first(), null);
    }

    public function testCreateWithElements() {
        $list = new DataSet(array(
            $this->daniel,
            $this->kathi,
            $this->patrick
        ));

        $this->assertEqual($list->count(), 3);
        $this->assertEqual(count($list), 3);
        $this->assertEqual($list->first(), $this->daniel);
        $this->assertEqual($list[1], $this->kathi);
        $this->assertEqual($list[2], $this->patrick);
    }

    public function testRemoveAdd() {
        $list = new DataSet(array(
            $this->daniel,
            $this->kathi,
            $this->patrick
        ));

        $list->add($this->janine);

        $this->assertEqual($list->count(), 4);
        $this->assertEqual($list[3], $this->janine);

        $list->remove($this->kathi);

        $this->assertEqual($list->count(), 3);
        $this->assertEqual($list[2], $this->janine);
        $this->assertEqual($list[3], null);

        $list->add($this->kathi);
        $list->add($this->kathi);

        $this->assertEqual($list->count(), 5);
        $this->assertEqual($list[2], $this->janine);
        $this->assertEqual($list[3], $this->kathi);
        $this->assertEqual($list[4], $this->kathi);
    }

    public function testRemoveDuplicatesByNumber() {
        $list = new DataSet(array(
            $this->daniel,
            $this->julian,
            $this->nik
        ));

        $list->removeDuplicates("age");

        $this->assertEqual($list->count(), 2);
        $this->assertEqual($list[0], $this->daniel);
        $this->assertEqual($list[1], $this->nik);
    }

    public function testRemoveDuplicatesByString() {
        $list = new DataSet(array(
            $this->daniel,
            $this->kathi,
            $this->kathi,
            $this->daniel
        ));

        $list->removeDuplicates("name");

        $this->assertEqual($list->count(), 2);
        $this->assertEqual($list[1], $this->kathi);
    }


    public function testSort() {
        $list = new DataSet($orgArray = array(
            $this->daniel,
            $this->kathi,
            $this->janine,
            $this->patrick,
            $this->julian
        ));

        $this->assertEqual($list->ToArray(), $orgArray);

        $sortedList = $list->sort("age", "asc");
        $age = 0;
        foreach($sortedList as $person) {
            $this->assertFalse($age > $person->age);
            $age = $person->age;
        }

        $this->assertNotEqual($list->ToArray(), $orgArray);
        $this->assertEqual($list->count(), $sortedList->count());

        $sortByGenderAndName = $list->sort(array("gender" => "asc", "age" => "asc"));
        $inW = false;
        $age = 0;
        foreach($sortByGenderAndName as $person) {
            if($person->gender == "W") {
                if(!$inW) {
                    $inW = true;
                    $age = 0;
                }
            } else if($inW) {
                $this->assertTrue(false);
            }
            $this->assertFalse($age > $person->age);
            $age = $person->age;
        }
    }

    /**
     * tests pagination abilities.
     */
    public function testPagination() {
        $set = new DataSet($org = array(
            $this->daniel,
            $this->kathi,
            $this->janine,
            $this->patrick,
            $this->julian,
            $this->daniel,
            $this->kathi,
            $this->janine,
            $this->patrick,
            $this->julian,
            $this->daniel,
            $this->kathi,
            $this->janine,
            $this->patrick,
            $this->julian,
            $this->daniel,
            $this->kathi,
            $this->janine,
            $this->patrick,
            $this->julian
        ));

        $set->activatePagination(1, 5);
        $this->assertTrue($set->isPagination());

        $this->assertEqual($set->getPageCount(), 4);
        $this->assertEqual($set->count(), 5);

        $set->setPage(2);

        $this->assertEqual($set->getPageCount(), 4);
        $this->assertEqual($set->count(), 5);
        $this->assertEqual($set->pageBefore(), 1);
        $this->assertEqual($set->nextPage(), 3);

        $set->setPage(4);
        $this->assertFalse($set->isNextPage());
        $this->assertTrue($set->isPageBefore());
        $this->assertEqual($set->last(), $this->julian);

        $set->filter("name", "Patrick");

        $this->assertEqual($set->getPage(), 1);
        $this->assertEqual($set->last(), $this->patrick);

        $set->filter();

        $this->assertEqual($set->getPage(), 4);
    }

    public function testFilter() {
        $list = new DataSet($orgArray = array(
            $this->daniel,
            $this->kathi,
            $this->janine,
            $this->patrick,
            $this->julian
        ));

        $filteredList = $list->filter(array("name" => array("LIKE", "Janine")));
        $this->assertEqual($filteredList->first(), $this->janine);
        $this->assertEqual($filteredList->count(), 1);

        $this->assertEqual($list->filter(array("name" => "janine"))->count(), 0);

        $this->assertEqual($list->first(), null);
        // reset filter
        $list->filter();
        $this->assertEqual($list->first(), $this->daniel);

        $advancedFilter = $list->filter(array("age" => array(">=", 20)));
        foreach($advancedFilter as $person) {
            $this->assertFalse($person->age < 20);
        }

        $this->assertEqual($list->find("name", "patrick", true), null);

        // reset filter
        $list->filter();
        $this->assertEqual($list->find("name", "patrick", true), $this->patrick);
        $this->assertNull($list->find("name", "patrick"));

        $list->addFilter(array("name" => array("LIKE", "Janine")));
        $this->assertEqual($filteredList->first(), $this->janine);
        $list->addFilter(array("age" => 19));
        $this->assertEqual($filteredList->first(), $this->janine);
        $list->addFilter(array("age" => 21));
        $this->assertEqual($filteredList->first(), null);
    }

    public function testMove() {
        $list = new DataSet($orgArray = array(
            $this->daniel,
            $this->kathi,
            $this->janine,
            $this->patrick,
            $this->julian
        ));

        $list->moveBehind($this->daniel, $this->janine);

        $this->assertEqual($list->first(), $this->kathi);
        $this->assertEqual($list[1], $this->janine);
        $this->assertEqual($list[2], $this->daniel);

        $list->moveBefore($this->daniel, $this->janine);

        $this->assertEqual($list->first(), $this->kathi);
        $this->assertEqual($list[2], $this->janine);
        $this->assertEqual($list[1], $this->daniel);

        $list->remove($this->daniel);

        $this->assertThrows(function() use($list) {
            $list->moveBefore($this->janine, $this->daniel);
        }, "ItemNotFoundException");

        $list->moveBefore($this->daniel, $this->janine, true);
        $this->assertEqual($list[1], $this->daniel);
    }

    public function testMoveSort() {
        $this->unittestMoveSort(true);
        $this->unittestMoveSort(false);
    }

    public function unittestMoveSort($activePagination) {
        $list = new DataSet($orgArray = array(
            $this->daniel,
            $this->kathi,
            $this->janine,
            $this->patrick,
            $this->julian
        ));

        if($activePagination) {
            $list->activatePagination();
        }

        $list->sort("age", "DESC");

        $list->moveBefore($list->find("name", "Patrick"), $list->find("name", "Kathi"));

        $this->assertEqual($list->first(), $this->patrick);
        $this->assertEqual($list[1], $this->kathi);
        $this->assertEqual($list[2], $this->daniel);
        $this->assertEqual($list[3], $this->julian);

        $list->filter("age", array(">", 0));

        $this->assertEqual($list->first(), $this->patrick);
        $this->assertEqual($list[1], $this->kathi);
        $this->assertEqual($list[2], $this->daniel);
        $this->assertEqual($list[3], $this->julian);
    }

    public function testCustomised() {
        $set = new DataSet();

        $set->customise(array(
            "blub" => "abc"
        ));

        $this->assertEqual($set->blub, "abc");

        // test if you can override customise
        $set->blub = 123;

        $this->assertEqual($set->blub, "abc");

        $set->add(array(
            "tada" => 123
        ));

        $this->assertEqual($set[0]->blub, "abc");

        foreach($set as $record) {
            $this->assertEqual($record->blub, "abc");
            $this->assertEqual($record->tada, 123);
        }

        foreach($set->getObjectWithoutCustomisation() as $record) {
            $this->assertEqual($record->blub, null);
            $this->assertEqual($record->tada, 123);
        }
    }

    public function testCustomiseByMySelf() {
        $set = new DataSet(array(
            array("test" => 123),
            array("test" => 345)
        ));

        $set->customise(array(
            "blub" => 123
        ));

        foreach($set as $record) {
            $this->assertEqual($record->blub, 123);
            $record->customise(array(
                "blah" => $record->test
            ));
            $this->assertEqual($record->blah, $record->test);
        }

        $objectWithoutCust = $set->getObjectWithoutCustomisation();
        foreach($objectWithoutCust as $record) {
            $this->assertEqual($record->blub, null);
            $this->assertEqual($record->blah, $record->test);
        }

        $set[1]->customise(array(
            "blah" => 123
        ));

        foreach($set as $record) {
            if($record->test == 345) {
                $this->assertEqual($record->blah, 123);
            }
        }
    }


    /**
     * tests iterator with delete.
     */
    public function testIterator() {
        $data = array(
            $this->daniel,
            $this->janine,
            $this->kathi,
            $this->julian,
            $this->patrick
        );
        $this->unittestIterator($data, 0);
        $this->unittestIterator($data, 1);
        $this->unittestIterator($data, 2);
        $this->unittestIterator($data, 3);
        $this->unittestIterator($data, 4);
    }

    /**
     * @param array $data
     * @param int $removePosition
     */
    public function unittestIterator($data, $removePosition) {
        $list = new ArrayList($data);

        $i = 0;
        foreach($list as $record) {
            if($i == $removePosition) {
                $list->remove($record);
            }
            $this->assertEqual($data[$i], $record);

            $i++;
        }

        $i = 0;
        foreach($list as $record) {
            if($i >= $removePosition) {
                $this->assertEqual($data[$i + 1], $record);
            } else {
                $this->assertEqual($data[$i], $record);
            }

            $i++;
        }
    }

    /**
     * tests if array stays the same if removing within iterator.
     */
    public function testIteratingStaysWhileRemoving() {
        $data = array(
            $this->daniel,
            $this->janine,
            $this->daniel,
            $this->janine
        );

        $i = 0;
        $list = new DataSet($data);
        foreach($list as $record) {
            if($i == 0) {
                $list->remove($record);
            }

            if($i % 2 == 1) {
                $this->assertEqual($record, $this->janine);
            } else {
                $this->assertEqual($record, $this->daniel);
            }
            $i++;
        }
    }

    /**
     * tests if array stays the same if removing within iterator, but it changes afterwards.
     */
    public function testIteratingChangeAfterDeleteAndIteration() {
        $data = array(
            $this->daniel,
            $this->janine,
            $this->daniel,
            $this->janine
        );

        $i = 0;
        $list = new DataSet($data);
        foreach($list as $record) {
            if($i == 0) {
                $list->remove($record);
            }
            $i++;
        }

        foreach($list as $record) {
            if($i == 1) {
                $this->assertEqual($this->daniel, $record);
            } else {
                $this->assertEqual($this->janine, $record);
            }
            $i++;
        }
    }

    public function testViewable() {
        $dataset = new DataSet(array(
            "blub" => array(
                "blah" => 123
            )
        ));
        $this->assertEqual($dataset->doObject("this"), $dataset);

        $dataset->customised["this"] = $dataset->first();
        $this->assertEqual($dataset->getOffset("this"), $dataset->first());
        $this->assertEqual($dataset->doObject("this"), $dataset->first());
    }

    /**
     * tests if group-by is working in-place.
     */
    public function testGroupByInPlace() {
        $set = new DataSet(array(
            $this->daniel,
            $this->janine,
            $this->julian,
            $this->nik
        ));

        $this->assertEqual($set, $set->groupBy("age"));
        $this->assertEqual($set->count(), 3);

        foreach($set as $record) {
            $this->assertIsA($record, IDataSet::class);
        }
    }

    /**
     * tests if group-by is working with a reset.
     */
    public function testGroupByReset() {
        $set = new DataSet(array(
            $this->daniel,
            $this->janine,
            $this->julian,
            $this->nik
        ));

        $set->groupBy("age");
        $this->assertEqual($set->count(), 3);

        $set->groupBy(null);
        $this->assertEqual($set->count(), 4);
    }

    /**
     * tests if group-by sets the one field to a value.
     */
    public function testGroupByEachSetWithValue() {
        $set = new DataSet(array(
            $this->daniel,
            $this->janine,
            $this->julian,
            $this->nik
        ));

        $set->groupBy("age");
        /** @var IDataSet|ViewAccessableData $record */
        foreach($set as $record) {
            $this->assertEqual($record->first()->age, $record->age);
        }
    }

    /**
     * tests if group-by sort is possible.
     */
    public function testGroupBySort() {
        $set = new DataSet(array(
            $this->daniel,
            $this->janine,
            $this->julian,
            $this->nik
        ));

        $set->groupBy("age");
        $set->sort("age");
        $age = $set->first()->first()->age;
        /** @var ViewAccessableData|IDataSet $record */
        foreach($set as $record) {
            $this->assertTrue($record->age >= $age);
            $age = $record->age;
        }
    }

    /**
     * tests if count is correctly responding.
     */
    public function testCount() {
        $set = new DataSet(array(
            $this->daniel,
            $this->janine,
            $this->julian,
            $this->nik
        ));

        $this->assertEqual(4, $set->count());
    }

    /**
     * tests if count is correctly working with pagination.
     */
    public function testPaginatedCount() {
        $set = new DataSet(array(
            $this->daniel,
            $this->janine,
            $this->julian,
            $this->nik
        ));
        $set->activatePagination(1, 2);

        $this->assertEqual(2, $set->count());
    }

    /**
     * tests if countWholeSet is correctly responding.
     */
    public function testCountWholeSet() {
        $set = new DataSet(array(
            $this->daniel,
            $this->janine,
            $this->julian,
            $this->nik
        ));

        $this->assertEqual(4, $set->countWholeSet());
    }

    /**
     * tests if countWholeSet is correctly working with pagination.
     */
    public function testPaginatedCountWholeSet() {
        $set = new DataSet(array(
            $this->daniel,
            $this->janine,
            $this->julian,
            $this->nik
        ));
        $set->activatePagination(1, 2);

        $this->assertEqual(4, $set->countWholeSet());
    }

    /**
     * Tests if getFilterFromArgs returns the same string if string was given.
     */
    public function testgetFilterFromArgsString() {
        $str = "a = 1 OR b = 1";
        $this->assertEqual($str, DataSet::getFilterFromArgs($str));
    }
}
