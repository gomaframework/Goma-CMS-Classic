<?php

namespace Goma\Form\Dropdown;

use Convert;
use DataNotFoundException;
use LogicException;

defined("IN_GOMA") or die();

/**
 * This class can be used to provide an dropdown in the natural select way.
 * Attention: it is still async. If you want to have a synchronous dropdown, use Select instead.
 *
 * @package Goma\Form\Dropdown
 *
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 */
class ArrayDataSource implements IDropdownDataSource
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var int|null
     */
    protected $maxItems;

    /**
     * ArrayDataSource constructor.
     * Array must be associative.
     *
     * @param array $options
     * @param int|null $maxItems null for unlimited
     */
    public function __construct($options, $maxItems = 1)
    {
        if (!\ArrayLib::isAssocArray($options)) {
            throw new \InvalidArgumentException("Array must be associative.");
        }

        $this->options = $options;
        $this->maxItems = $maxItems;
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
        return $this->maxItems;
    }

    /**
     * extend options of selectsize, see {@link https://github.com/selectize/selectize.js/blob/master/docs/usage.md}
     *
     * @param ExtendedDropdown $dropdown
     * @return array
     */
    public function extendOptions($dropdown)
    {
        return array();
    }

    /**
     * function should return value which should be added to the current set of values.
     *
     * @param ExtendedDropdown $dropdown
     * @param string $input
     * @return DropdownItem|null
     */
    public function create($dropdown, $input)
    {
        return null;
    }

    /**
     * This method is used to get selected values if these are not able to infer from post information.
     * It shall be used to collect this data from the model.
     *
     * For maxItemsCount != 1: function MUST return set of DropdownItem, which are selected.
     * For maxItemsCount = 1: function MUST return only one DropdownItem or null.
     *
     * @param ExtendedDropdown $dropdown
     * @param null $modelData data which is infered from parent or post
     * @return DropdownItem|DropdownItem[]|null
     * @throws DataNotFoundException
     */
    public function getSelectedValues($dropdown, $modelData = null)
    {
        if($dropdown->getModel()) {
            if ($this->maxItemsCount($dropdown) > 1) {
                return array_map(function ($modelItem) {
                    if (isset($this->options[$modelItem])) {
                        return new DropdownItem($modelItem,
                            Convert::raw2text($this->options[$modelItem]),
                            Convert::raw2text($this->options[$modelItem])
                        );
                    } else {
                        throw new DataNotFoundException("Selected item in model $modelItem is not available in options.");
                    }
                }, (array)$dropdown->getModel()
                );
            } else {
                if (isset($this->options[$dropdown->getModel()])) {
                    return new DropdownItem($dropdown->getModel(),
                        Convert::raw2text($this->options[$dropdown->getModel()]),
                        Convert::raw2text($this->options[$dropdown->getModel()])
                    );
                } else {
                    throw new DataNotFoundException("Selected model is not available in options.");
                }
            }
        }

        return null;
    }

    /**
     * function MUST return set of values, which are addable.
     * it is either an array of arrays or an array of strings.
     *
     * @param ExtendedDropdown $dropdown
     * @param string $searchTerm
     * @param int $page
     * @param int $perPage
     * @return DropdownItem[]
     */
    public function getData($dropdown, $searchTerm, $page, $perPage)
    {
        return array_map(function($value, $title){
            return new DropdownItem($value,
                Convert::raw2text($title),
                Convert::raw2text($title)
            );
        }, array_keys($this->options), $this->options);
    }

    /**
     * returns result values of selected values. This will be the result of the FormField.
     *
     * @param ExtendedDropdown $dropdown
     * @param $selected mixed[]|mixed values which are selected. Only one value if maxItemsCount = 1
     * @return mixed
     */
    public function getResultValues($dropdown, $selected)
    {
        return $selected;
    }

    /**
     * this function MUST return javascript or null. It provides a way to change the default options of selectize.
     * The returned javascript is executed right before the creation of selectize.
     *
     * JavaScript Info:
     * options is provided as options in javascript and contains already all options created by the other functions.
     * form is available as form and
     * field as field.
     *
     * @param ExtendedDropdown $dropdown
     * @return null|string
     */
    public function jsExtendOptionsBeforeCreate($dropdown)
    {
        return null;
    }
}
