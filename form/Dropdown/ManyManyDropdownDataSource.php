<?php

namespace Goma\Form\Dropdown;

use DataObject;
use InvalidArgumentException;
use ModelHasOneRelationshipInfo;

defined("IN_GOMA") or die();

/**
 * This datasource can be used with ExtendedDropdowns while having has-one-relationships.
 *
 * @package Goma\Form\Dropdown
 *
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 */
class ManyManyDropdownDataSource extends HasOneDropdownDataSource
{
    /**
     * @var bool
     */
    protected $sortable = false;

    /**
     * @return bool
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * @param bool $sortable
     * @return ManyManyDropdownDataSource
     */
    public function setSortable($sortable)
    {
        $this->sortable = $sortable;
        return $this;
    }

    /**
     * defines how many items are selectable. Use a value greater than 1 for multiselect.
     *
     * @param ExtendedDropdown $dropdown
     * @return int|null null for unlimited
     */
    public function maxItemsCount($dropdown)
    {
        return null;
    }

    /**
     * defines if dropdown should allow drag and drop.
     *
     * @param ExtendedDropdown $dropdown
     * @return bool
     */
    public function allowDragnDrop($dropdown)
    {
        return $this->sortable;
    }

    /**
     * This method is used to get selected values if these are not able to infer from post information.
     * It shall be used to collect this data from the model.
     *
     * For maxItemsCount != 1: function MUST return set of DropdownItem, which are selected.
     * For maxItemsCount = 1: function MUST return only one DropdownItem or null.
     *
     * @param ExtendedDropdown $dropdown
     * @param null $modelData data which is inferred from parent or $dropdown->model
     * @return DropdownItem|DropdownItem[]|null
     */
    public function getSelectedValues($dropdown, $modelData = null)
    {
        if(is_array($modelData)) {
            $items = $this->getManyManyDataSet($dropdown)->filter(array("versionid" => $modelData));
            if($items->count() > 0) {
                $representations = array();
                foreach($modelData as $item) {
                    $representations[] = $this->createDropdownItemFromValue(
                        $items->find("versionid", $item)
                    );
                }
                return $representations;
            }
        } else if(is_a($modelData, \IDataSet::class)) {
            return $this->createDropdownItemFromValues($modelData);
        }

        return null;
    }

    /**
     * function MUST return set of DropdownItem, which are selectable.
     *
     * @param ExtendedDropdown $dropdown
     * @param string $searchTerm
     * @param int $page
     * @param int $perPage
     * @return DropdownItem[]
     */
    public function getData($dropdown, $searchTerm, $page, $perPage)
    {
        $items = $this->getManyManyDataSet($dropdown)->search($searchTerm)->activatePagination($page, $perPage);
        return array($items->mapToArray(function($item){
            /** @var \DataObject $item */
            return $this->createDropdownItemFromValue($item);
        }), $items->countWholeSet());
    }

    /**
     * @param ExtendedDropdown $dropdown
     * @return \DataObject[]|\DataObjectSet
     */
    protected function getManyManyDataSet($dropdown) {
        $set = \DataObject::get($this->getManyManyTarget($dropdown), $this->filter);
        $set->setVersion($this->useStateData ? DataObject::VERSION_STATE : DataObject::VERSION_PUBLISHED);
        return $set;
    }

    /**
     * @param ExtendedDropdown $dropdown
     * @return string
     */
    protected function getManyManyTarget($dropdown) {
        // get has-one from model
        if (is_a($dropdown->getParent()->getModel(), \DataObject::class)) {
            /** @var ModelHasOneRelationshipInfo[] $many_many */
            $many_many = $dropdown->getParent()->getModel()->ManyManyRelationships();
        }

        $relation = strtolower($dropdown->getName());
        if (isset($many_many[$relation])) {
            return $many_many[$relation]->getTargetClass();
        }

        if (isset($many_many[substr($relation, 0, -2)])) {
            return $many_many[substr($relation, 0, -2)]->getTargetClass();
        }

        throw new InvalidArgumentException("Could not find ManyMany-Relationship " . $dropdown->getName());
    }

    /**
     * returns result values of selected values. This will be the result of the FormField
     * if data is collected by post data. Else normal model is used.
     *
     * @param ExtendedDropdown $dropdown
     * @param $selected DropdownItem[]|mixed values which are selected.
     * @return mixed
     */
    public function getResultValues($dropdown, $selected)
    {
        if($this->returnModel) {
            return array_map(function ($item){
                /** @var DropdownItem $item */
                return $item->getValue();
            }, $selected);
        }

        return array_map(function ($item){
            /** @var DropdownItem $item */
            return $item->getValue()->versionid;
        }, $selected);
    }

    /**
     * this function MUST return javascript or null. It provides a way to change the default options of selectize.
     * The returned javascript is executed right before the creation of selectize.
     *
     * JavaScript Info:
     * options is provided as options in javascript and contains already all options created by the other functions.
     * form is available as form and
     * field as field
     * ExtendedDropdown as this.
     *
     * If something is return this is used for options.
     *
     * @param ExtendedDropdown $dropdown
     * @return null|string
     */
    public function jsExtendOptionsBeforeCreate($dropdown)
    {

    }

    /**
     * @param DataObject[]|\IDataSet $items
     * @param int[]|null $inOrderOfVersionIds
     * @return DropdownItem[]
     */
    protected function createDropdownItemFromValues($items)
    {
        $representations = array();
        foreach($items as $item) {
            $representations[] = $this->createDropdownItemFromValue($item);
        }
        return $representations;
    }
}
