<?php

namespace Goma\Test\Form;

use Form;
use Goma\Form\Dropdown\DropdownItem;

defined("IN_GOMA") or die();

/**
 * Unit-Tests for HasOneDropdownDataSource-Class.
 *
 * @package		Goma\Test\Form
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class HasOneDropdownDataSourceTest extends \GomaUnitTest
{
    /**
     * tests if getSelectedValues gets valid results for numbers.
     *
     * 1. Create Form $form
     * 2. Create MockDBObjectHasOne $hasOneTarget
     * 3. Write $hasOneTarget to DB
     * 4. Set model of $form to $hasOneTarget
     * 5. Create HasOneDropdown $dropdown, add to $form
     * 6. Call  $dropdown->getDataSource()->getSelectedValues($dropdown, $hasOneTarget->id), set to $selected
     * 7. Assert that $selected is DropdownItem
     * 8. Assert that $selected->getValue()->id is equal to $hasOneTarget->id
     */
    public function testGetSelectedValuesNumber() {
        try {
            $form = new Form(new \Controller(), "testForm");
            $hasOneTarget = new \MockDBObjectHasOne();
            $hasOneTarget->writeToDB(false, true);
            $form->setModel($hasOneTarget);
            $form->add($dropdown = new \HasOneDropdown("hasonerelation", "hasone"));

            /** @var DropdownItem $selected */
            $selected = $dropdown->getDataSource()->getSelectedValues($dropdown, $hasOneTarget->id);
            $this->assertInstanceOf(DropdownItem::class, $selected);
            $this->assertEqual($hasOneTarget->id, $selected->getValue()->id);
        } finally {
            if($hasOneTarget) {
                $hasOneTarget->remove(true);
            }
        }
    }

    /**
     * tests if getSelectedValues gets valid results for DataObjects.
     *
     * 1. Create Form $form
     * 2. Create MockDBObjectHasOne $hasOneTarget
     * 3. Write $hasOneTarget to DB
     * 4. Set model of $form to $hasOneTarget
     * 5. Create HasOneDropdown $dropdown, add to $form
     * 6. Call  $dropdown->getDataSource()->getSelectedValues($dropdown, $hasOneTarget), set to $selected
     * 7. Assert that $selected is DropdownItem
     * 8. Assert that $selected->getValue()->id is equal to $hasOneTarget->id
     */
    public function testGetSelectedValuesObject() {
        try {
            $form = new Form(new \Controller(), "testForm");
            $hasOneTarget = new \MockDBObjectHasOne();
            $hasOneTarget->writeToDB(false, true);
            $form->setModel($hasOneTarget);
            $form->add($dropdown = new \HasOneDropdown("hasonerelation", "hasone"));

            /** @var DropdownItem $selected */
            $selected = $dropdown->getDataSource()->getSelectedValues($dropdown, $hasOneTarget);
            $this->assertInstanceOf(DropdownItem::class, $selected);
            $this->assertEqual($hasOneTarget->id, $selected->getValue()->id);
        } finally {
            if($hasOneTarget) {
                $hasOneTarget->remove(true);
            }
        }
    }

    /**
     * tests if getSelectedValues gets valid results for hashValue of DataObject (used as value-representation).
     *
     * 1. Create Form $form
     * 2. Create MockDBObjectHasOne $hasOneTarget
     * 3. Write $hasOneTarget to DB
     * 4. Set model of $form to $hasOneTarget
     * 5. Create HasOneDropdown $dropdown, add to $form
     * 6. Call  $dropdown->getDataSource()->getSelectedValues($dropdown, (new DropdownItem($hasOneTarget, "blah"))->getValueRepresentation()), set to $selected
     * 7. Assert that $selected is DropdownItem
     * 8. Assert that $selected->getValue()->id is equal to $hasOneTarget->id
     */
    public function testGetSelectedValuesHasValue() {
        try {
            $form = new Form(new \Controller(), "testForm");
            $hasOneTarget = new \MockDBObjectHasOne();
            $hasOneTarget->writeToDB(false, true);
            $form->setModel($hasOneTarget);
            $form->add($dropdown = new \HasOneDropdown("hasonerelation", "hasone"));

            /** @var DropdownItem $selected */
            $selected = $dropdown->getDataSource()->getSelectedValues($dropdown, (new DropdownItem($hasOneTarget, "blah"))->getValueRepresentation());
            $this->assertInstanceOf(DropdownItem::class, $selected);
            $this->assertEqual($hasOneTarget->id, $selected->getValue()->id);
        } finally {
            if($hasOneTarget) {
                $hasOneTarget->remove(true);
            }
        }
    }
}
