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
class HasOneDropdownDataSource implements IDropdownDataSource
{

    /**
     * defines if to return object or id.
     * If id is returned, name of field shall end on "id".
     * @var bool
     */
    protected $returnModel = false;

    /**
     * defines template for option representation.
     *
     * @var string
     */
    protected $optionTemplate = "form/DropdownOption.html";

    /**
     * defines template for option representation.
     *
     * @var string
     */
    protected $inputTemplate = "form/DropdownInput.html";

    /**
     * defines field for title.
     * also shown in input.
     * @var string
     */
    protected $titleField = "title";

    /**
     * defines standard field for details
     * @var string|null
     */
    protected $infoField = null;

    /**
     * filter for relationship.
     *
     * @var array
     */
    protected $filter = array();

    /**
     * limit of items per query.
     *
     * @var int
     */
    protected $limit = 10;

    /**
     * @var bool
     */
    protected $useStateData = false;

    /**
     * HasOneDropdownDataSource constructor.
     * @param bool $returnModel
     * @param null|string $titleField
     * @param null|string $infoField
     * @param null|string $optionTemplate
     * @param null|string $inputTemplate
     */
    public function __construct($returnModel = false,  $titleField = null, $infoField = null, $optionTemplate = null, $inputTemplate = null)
    {
        $this->returnModel = $returnModel;
        $this->optionTemplate = isset($optionTemplate) ? $optionTemplate : $this->optionTemplate;
        $this->inputTemplate = isset($inputTemplate) ? $inputTemplate : $this->inputTemplate;;
        $this->titleField = isset($titleField) ? $titleField : $this->titleField;
        $this->infoField = isset($infoField) ? $infoField : $this->infoField;
    }

    /**
     * @return array
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param array $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return bool
     */
    public function getReturnModel()
    {
        return $this->returnModel;
    }

    /**
     * @param bool $returnModel
     * @return $this
     */
    public function setReturnModel($returnModel)
    {
        $this->returnModel = $returnModel;
        return $this;
    }

    /**
     * @return string
     */
    public function getOptionTemplate()
    {
        return $this->optionTemplate;
    }

    /**
     * @param string $optionTemplate
     * @return $this
     */
    public function setOptionTemplate($optionTemplate)
    {
        $this->optionTemplate = $optionTemplate;
        return $this;
    }

    /**
     * @return string
     */
    public function getInputTemplate()
    {
        return $this->inputTemplate;
    }

    /**
     * @param string $inputTemplate
     * @return $this
     */
    public function setInputTemplate($inputTemplate)
    {
        $this->inputTemplate = $inputTemplate;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitleField()
    {
        return $this->titleField;
    }

    /**
     * @param string $titleField
     * @return $this
     */
    public function setTitleField($titleField)
    {
        $this->titleField = $titleField;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getInfoField()
    {
        return $this->infoField;
    }

    /**
     * @param null|string $infoField
     * @return $this
     */
    public function setInfoField($infoField)
    {
        $this->infoField = $infoField;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseStateData()
    {
        return $this->useStateData;
    }

    /**
     * @param bool $useStateData
     * @return $this
     */
    public function setUseStateData($useStateData)
    {
        $this->useStateData = $useStateData;
        return $this;
    }

    /**
     * defines if dropdown should allow drag and drop.
     *
     * @param ExtendedDropdown $dropdown
     * @return bool
     */
    public function allowDragnDrop($dropdown)
    {
        return false;
    }

    /**
     * defines if creation is allowed.
     *
     * @param ExtendedDropdown $dropdown
     * @return bool
     */
    public function allowCreation($dropdown)
    {
        return false;
    }

    /**
     * defines how many items are selectable. Use a value greater than 1 for multiselect.
     *
     * @param ExtendedDropdown $dropdown
     * @return int|null null for unlimited
     */
    public function maxItemsCount($dropdown)
    {
        return 1;
    }

    /**
     * extend options of selectsize, see {@link https://github.com/selectize/selectize.js/blob/master/docs/usage.md}
     *
     * @param ExtendedDropdown $dropdown
     * @return array
     */
    public function extendOptions($dropdown)
    {

    }

    /**
     * function should return DropdownItem, which is generated out of input.
     *
     * @param ExtendedDropdown $dropdown
     * @param string $input
     * @return DropdownItem|null
     */
    public function create($dropdown, $input)
    {

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
        if(\RegexpUtil::isNumber($modelData)) {
            if($item = $this->getHasOneDataSet($dropdown)->filter(array("id" => $modelData))->first()) {
                return $this->createDropdownItemFromValue($item);
            }
        } else if(is_a($modelData, \DataObject::class)) {
            return $this->createDropdownItemFromValue($modelData);
        } else {
            if($item = $this->getHasOneDataSet($dropdown)->filter(array("hashvalue" => $modelData))->first()) {
                return $this->createDropdownItemFromValue($item);
            }
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
        $items = $this->getHasOneDataSet($dropdown)->search($searchTerm)->activatePagination($page, $perPage);
        return array($items->mapToArray(function($item){
            /** @var \DataObject $item */
            return $this->createDropdownItemFromValue($item);
        }), $items->countWholeSet());
    }

    /**
     * @param \DataObject $item
     * @return DropdownItem
     */
    protected function createDropdownItemFromValue($item) {
        $item = $item->customise(array(
            "titleFieldValue" => \ViewAccessableData::getItemProp($item, $this->titleField),
            "detailFieldValue" => \ViewAccessableData::getItemProp($item, $this->infoField)
        ));

        return new DropdownItem($item,
            $item->renderWith($this->inputTemplate),
            $item->renderWith($this->optionTemplate));
    }

    /**
     * @param ExtendedDropdown $dropdown
     * @return \DataObject[]|\DataObjectSet
     */
    protected function getHasOneDataSet($dropdown) {
        $set = \DataObject::get($this->getHasOneTarget($dropdown), $this->filter);
        $set->setVersion($this->useStateData ? DataObject::VERSION_STATE : DataObject::VERSION_PUBLISHED);
        return $set;
    }

    /**
     * @param ExtendedDropdown $dropdown
     * @return string
     */
    protected function getHasOneTarget($dropdown) {
        // get has-one from model
        if (is_a($dropdown->getParent()->getModel(), \DataObject::class)) {
            /** @var ModelHasOneRelationshipInfo[] $has_one */
            $has_one = $dropdown->getParent()->getModel()->hasOne();
        }

      	$relation = strtolower($dropdown->getName());
        if (isset($has_one[$relation])) {
            return $has_one[$relation]->getTargetClass();
        }

        if (isset($has_one[substr($relation, 0, -2)])) {
            return $has_one[substr($relation, 0, -2)]->getTargetClass();
        }

        throw new InvalidArgumentException("Could not find HasOne-Relationship " . $dropdown->getName());
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
            return isset($selected[0]) ? $selected[0]->getValue() : null;
        }

        return isset($selected[0]) ? $selected[0]->getValue()->id : null;
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
}
