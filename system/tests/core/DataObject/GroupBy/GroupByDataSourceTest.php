<?php
namespace Goma\Test\Model\GroupBy;

use DataObject;
use DataObjectSet;
use Goma\Model\Group\GroupedDataObjectSetDataSource;
use Goma\Test\Model\DumpDBElementPerson;
use Goma\Test\Model\MockIDataObjectSetDataSource;
use GomaUnitTest;
use ReflectionProperty;

defined("IN_GOMA") OR die();

/**
 * Unit-Tests for GroupedDataObjectSetDataSource-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class GroupByDataSourceTest extends GomaUnitTest {
    /**
     * tests if source is assigned correctly.
     */
    public function testAssignSource() {
        $set = new DataObjectSet(DumpDBElementPerson::class);
        $set->setVersion(DataObject::VERSION_PUBLISHED);

        /** @var MockIDataObjectSetDataSource $source */
        $source = $set->getDbDataSource();

        $grouped = $set->groupBy("test");
        $newSource = $grouped->getDbDataSource();

        $this->assertIsA($newSource, GroupedDataObjectSetDataSource::class);
        $this->assertEqual($grouped->count(), 0);

        $property = new ReflectionProperty(GroupedDataObjectSetDataSource::class, "datasource");
        $property->setAccessible(true);
        $this->assertEqual($property->getValue($newSource), $source);
    }
}
