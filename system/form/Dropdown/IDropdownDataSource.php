<?php

namespace Goma\Form\Dropdown;

defined("IN_GOMA") or die();

/**
 * This is the interface of each data-source for an extendedDropdown.
 * Global definition of terms:
 *
 * * A "item" is one data item, which can be selected and has a representation. It is commonly represented as DropdownItem
 *
 * @package Goma\Form\Dropdown
 *
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 */
interface IDropdownDataSource
{
    #region configuration

    /**
     * defines if dropdown should allow drag and drop.
     *
     * @param ExtendedDropdown $dropdown
     * @return bool
     */
    public function allowDragnDrop($dropdown);

    /**
     * defines if creation is allowed.
     * If creation is allowed only under specific conditions, adding a createFilter to jsExtendOptionsBeforeCreate
     * can be used. e.g. "createFilter" => "/[a-z]+/"
     *
     * @param ExtendedDropdown $dropdown
     * @return bool
     */
    public function allowCreation($dropdown);

    /**
     * defines how many items are selectable. Use a value greater than 1 for multiselect.
     *
     * @param ExtendedDropdown $dropdown
     * @return int|null null for unlimited
     */
    public function maxItemsCount($dropdown);

    /**
     * extend options of selectsize, see {@link https://github.com/selectize/selectize.js/blob/master/docs/usage.md}
     *
     * @param ExtendedDropdown $dropdown
     * @return array
     */
    public function extendOptions($dropdown);

    #endregion

    #region functions, that return data items

    /**
     * function should return DropdownItem, which is generated out of input.
     * Throwing an exception here will result in an alert dialog for the user with the message of the exception.
     *
     * @param ExtendedDropdown $dropdown
     * @param string $input
     * @return DropdownItem|null
     */
    public function create($dropdown, $input);

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
    public function getSelectedValues($dropdown, $modelData = null);

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
    public function getData($dropdown, $searchTerm, $page, $perPage);

    #endregion

    #region form result generation

    /**
     * returns result values of selected values. This will be the result of the FormField
     * if data is collected by post data. Else normal model is used.
     *
     * @param ExtendedDropdown $dropdown
     * @param $selected DropdownItem[] values which are selected.
     * @return mixed
     */
    public function getResultValues($dropdown, $selected);

    #endregion

    #region Functions, which return JS for rendering and configuration

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
    public function jsExtendOptionsBeforeCreate($dropdown);

    #endregion
}
