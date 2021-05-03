<?php

namespace Goma\Test\Form;

use Goma\Form\Dropdown\DropdownItem;
use Goma\Form\Dropdown\ExtendedDropdown;
use stdClass;

defined("IN_GOMA") or die();

/**
 * Unit-Tests for ExtendedDropdown-Class.
 *
 * @package		Goma\Test\Form
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ExtendedDropdownTest extends \GomaUnitTest
{
    /**
     * tests if constructor assigns DataSource correctly.
     *
     * 1. Create MockDataSource $dataSource
     * 2. Create ExtendedDropdown $dropdown with $dataSource
     * 3. Assert That $dropdown->getDataSource() is identical to $dataSource
     */
    public function testInitWithDataSource() {
        $dataSource = new MockDataSourceSingleItem();
        $dropdown = new ExtendedDropdown("test", "test", $dataSource);
        $this->assertIdentical($dropdown->getDataSource(), $dataSource);
    }

    /**
     * tests if model is returned if getModel is called.
     *
     * 1. Set $value to "value1"
     * 2. Create MockDataSource $dataSource
     * 3. Create ExtendedDropdown $dropdown with $dataSource and value $value
     * 4. Assert That $dropdown->getModel() is equal to $value
     */
    public function testGetSelectedValuesCalledOnDataSourceWithValueString() {
        $value = "value1";
        $dataSource = new MockDataSourceSingleItem();
        $dropdown = new ExtendedDropdown("test", "test", $dataSource, $value);
        $this->assertEqual($value, $dropdown->getModel());
    }

    /**
     * tests if getSelectedValues is called on DataSource if model is queried.
     *
     * 1. Set $value to stdClass.
     * 2. Create MockDataSource $dataSource
     * 3. Create ExtendedDropdown $dropdown with $dataSource and value $value
     * 4. Assert That $dropdown->getModel() is equal to $value
     */
    public function testGetSelectedValuesCalledOnDataSourceWithValueObject() {
        $value = new StdClass();
        $dataSource = new MockDataSourceSingleItem();
        $dropdown = new ExtendedDropdown("test", "test", $dataSource, $value);
        $this->assertEqual($value, $dropdown->getModel());
    }

    /**
     * tests if getSelectedValues is called on DataSource if model is queried.
     *
     * 1. Set $value to array.
     * 2. Create MockDataSource $dataSource
     * 3. Create ExtendedDropdown $dropdown with $dataSource and value $value
     * 4. Assert That $dropdown->getModel() is equal to $value
     */
    public function testGetSelectedValuesCalledOnDataSourceWithValueArray() {
        $value = array(1);
        $dataSource = new MockDataSourceSingleItem();
        $dropdown = new ExtendedDropdown("test", "test", $dataSource, $value);
        $this->assertEqual($value, $dropdown->getModel());
    }

    /**
     * tests if exportBasicInfo has additional keys required by JS.
     *
     * 1. Set $value to "value1".
     * 2. Create MockDataSource $dataSource
     * 3. Create ExtendedDropdown $dropdown with $dataSource and value $value
     * 4. Create Form $form
     * 5. Add $dropdown to $form
     * 6. Call $dropdown->exportBasicInfo() set to $info
     * 7. Assert $info->getCustomised()["maxItemsCount"] is null
     * 8. Assert $info->getCustomised()["allowCreate"] is false
     * 9. Assert $info->getCustomised()["allowDrag"] is false
     * 10. Assert that $info->getCustomised()["selectedItemValues"] is equal to array($value)
     * 11. Assert that $info->getCustomised()["selectedItem"][0] is array
     * 12. Assert that $info->getCustomised()["selectedItem"][0]["valueRepresentation"] is equal to $value
     */
    public function testExportInfoJSKeys() {
        $value = "value1";
        $dataSource = new MockDataSourceSingleItem();
        $dropdown = new ExtendedDropdown("test", "test", $dataSource, $value);
        $form = new \Form(new \Controller(), "form");
        $form->add($dropdown);
        $info = $dropdown->exportBasicInfo();
        $this->assertNull($info->getCustomised()["maxItemsCount"]);
        $this->assertFalse($info->getCustomised()["allowCreate"]);
        $this->assertFalse($info->getCustomised()["allowDrag"]);
        $this->assertEqual(array($value), $info->getCustomised()["selectedItemValues"]);
        $this->assertIsA($info->getCustomised()["selectedItems"][0], "array");
        $this->assertEqual($value, $info->getCustomised()["selectedItems"][0]["valueRepresentation"]);
    }

    /**
     * tests if putToCache and getFromCache work.
     *
     * 1. Set $value to "value1".
     * 2. Create MockDataSource $dataSource
     * 3. Create ExtendedDropdown $dropdown with $dataSource and value $value
     * 4. Create Form $form
     * 5. Add $dropdown to $form
     * 6. Clone $dropdown to $clonedDropdown
     * 7. Create DropdownItem $dropdownItem1 with $value
     * 8. Put $dropdownItem1 to cache of $dropdown
     * 9. Try to get DropdownItem from cache with $value from $clonedDropdown
     * 10. Check if objects are equal
     */
    public function testPutToCacheGetFromCache() {
        $value = "value1";
        $dataSource = new MockDataSourceSingleItem();
        $dropdown = new ExtendedDropdown("test", "test", $dataSource, $value);
        $form = new \Form(new \Controller(), "form");
        $form->add($dropdown);
        $clonedDropdown = clone $dropdown;
        $dropdownItem1 = new DropdownItem($value, $value . "title", $value . "option");
        $dropdown->putToCache($dropdownItem1);
        $this->assertEqual($clonedDropdown->getFromCache($value), $dropdownItem1);
    }
}
