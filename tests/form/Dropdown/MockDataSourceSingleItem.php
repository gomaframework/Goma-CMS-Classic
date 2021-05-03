<?php

namespace Goma\Test\Form;

use Goma\Form\Dropdown\DropdownItem;
use Goma\Form\Dropdown\ExtendedDropdown;
use Goma\Form\Dropdown\IDropdownDataSource;

defined("IN_GOMA") or die();

/**
 * MockDataSource used for testing of ExtendedDropdown.
 *
 * @package		Goma\Test\Form
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class MockDataSourceSingleItem implements IDropdownDataSource
{

    /**
     * MockDataSource constructor.
     */
    public function __construct()
    {
    }

    /**
     * defines if dropdown should allow drag and drop.
     *
     * @param ExtendedDropdown $dropdown
     * @return bool
     */
    public function allowDragnDrop($dropdown)
    {
        // TODO: Implement allowDragnDrop() method.
    }

    /**
     * defines if creation is allowed.
     * If creation is allowed only under specific conditions, adding a createFilter to jsExtendOptionsBeforeCreate
     * can be used. e.g. "createFilter" => "/[a-z]+/"
     *
     * @param ExtendedDropdown $dropdown
     * @return bool
     */
    public function allowCreation($dropdown)
    {
        // TODO: Implement allowCreation() method.
    }

    /**
     * defines how many items are selectable. Use a value greater than 1 for multiselect.
     *
     * @param ExtendedDropdown $dropdown
     * @return int|null null for unlimited
     */
    public function maxItemsCount($dropdown)
    {
        // TODO: Implement maxItemsCount() method.
    }

    /**
     * extend options of selectsize, see {@link https://github.com/selectize/selectize.js/blob/master/docs/usage.md}
     *
     * @param ExtendedDropdown $dropdown
     * @return array
     */
    public function extendOptions($dropdown)
    {
        // TODO: Implement extendOptions() method.
    }

    /**
     * function should return DropdownItem, which is generated out of input.
     * Throwing an exception here will result in an alert dialog for the user with the message of the exception.
     *
     * @param ExtendedDropdown $dropdown
     * @param string $input
     * @return DropdownItem|null
     */
    public function create($dropdown, $input)
    {
        // TODO: Implement create() method.
    }

    /**
     * This method is used to get selected values if these are not able to infer from post information.
     * It shall be used to collect this data from the model.
     *
     * For maxItemsCount != 1: function MUST return set of DropdownItem, which are selected.
     * For maxItemsCount = 1: function MUST return only one DropdownItem or null.
     *
     * @param ExtendedDropdown $dropdown
     * @param null $modelData data which is infered from parent or $dropdown->model
     * @return DropdownItem|DropdownItem[]|null
     */
    public function getSelectedValues($dropdown, $modelData = null)
    {
        return new DropdownItem($modelData, "modelData", "modelDataOption");
    }

    /**
     * function MUST return set of DropdownItem, which are selectable.
     *
     * @param ExtendedDropdown $dropdown
     * @param string $searchTerm
     * @param int $page current page for pagination
     * @param int $perPage current expected number of items per page
     * @return array(DropdownItem[], int) array first element should be array of DropdownItems,
     * second maximum number of items for search term (null if unknown) (see https://confluence.goma-cms.org/display/GOMA/ExtendedDropdown)
     */
    public function getData($dropdown, $searchTerm, $page, $perPage)
    {
        // TODO: Implement getData() method.
    }

    /**
     * returns result values of selected values. This will be the result of the FormField
     * if data is collected by post data. Else normal model is used.
     *
     * @param ExtendedDropdown $dropdown
     * @param $selected DropdownItem[] values which are selected.
     * @return mixed
     */
    public function getResultValues($dropdown, $selected)
    {
        // TODO: Implement getResultValues() method.
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
        // TODO: Implement jsExtendOptionsBeforeCreate() method.
    }
}
