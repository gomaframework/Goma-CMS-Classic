<?php

namespace Goma\Form\Dropdown;

defined("IN_GOMA") or die();

/**
 * One DropdownItem represents an item, which is managed by ExtendedDropdown.
 * It has a value, a representation as
 * * item in the input field (inputRepresentation)
 * * option in dropdown (optionRepresentation)
 *
 * @package Goma\Form\Dropdown
 *
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 */
class DropdownItem
{
    /**
     * Value. If value is a string, int, null or boolean, this is also accessable in javascript, when querying the values of selectize.
     * For all other data types, this is replaced by a random string.
     *
     * @var mixed
     */
    protected $value;

    /**
     * @var string
     */
    protected $valueRepresentation;

    /**
     * Item representation in input field. This MUST be HTML.
     *
     * @var string
     */
    protected $inputRepresentation;

    /**
     * option representation in dropdown. This MUST be HTML.
     *
     * @var string
     */
    protected $optionRepresentation;

    /**
     * infos for searching.
     *
     * @var string
     */
    protected $searchInfo = "";

    /**
     * defines if search info should be set as $searchInfo.
     *
     * @var bool
     */
    protected $overrideSearch = false;

    /**
     * @param mixed $value
     * @param string $inputRepresentation This must be HTML
     * @param string $optionRepresentation This must be HTML
     */
    public function __construct($value, $inputRepresentation, $optionRepresentation = null)
    {
        $this->value = $value;
        $this->inputRepresentation = $inputRepresentation;
        $this->optionRepresentation = !isset($optionRepresentation) ? $inputRepresentation : $optionRepresentation;
    }

    /**
     * @return string
     */
    public function getValueRepresentation()
    {
        if(!$this->valueRepresentation) {
            if(is_null($this->value) || is_string($this->value) || is_numeric($this->value) || is_bool($this->value)) {
                $this->valueRepresentation = (string) $this->value;
            } else if(is_object($this->value) && \gObject::method_exists($this->value, "hashValue")) {
                $this->valueRepresentation = $this->value->hashValue();
            } else {
                $this->valueRepresentation = randomString(10);
            }
        }

        return $this->valueRepresentation;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getInputRepresentation()
    {
        return $this->inputRepresentation;
    }

    /**
     * @return string
     */
    public function getOptionRepresentation()
    {
        return $this->optionRepresentation;
    }

    /**
     * @return string
     */
    public function getSearchInfo() {
        if($this->overrideSearch) {
            return $this->searchInfo;
        }

        return strip_tags($this->optionRepresentation).strip_tags($this->inputRepresentation) . $this->searchInfo;
    }

    /**
     * sets the search info of this field.
     *
     * @param string $searchInfo
     * @return $this
     */
    public function setSearchInfo($searchInfo) {
        $this->searchInfo = $searchInfo;
        return $this;
    }

    /**
     * Sets if search info should exclude input and option representation or not.
     *
     * @param bool $overrideSearch
     * @return $this
     */
    public function setOverrideSearch($overrideSearch)
    {
        $this->overrideSearch = $overrideSearch;
        return $this;
    }

    /**
     * @param string $searchInfo
     * @return $this
     */
    public function addSearchInfo($searchInfo)
    {
        $this->searchInfo .= $searchInfo;

        return $this;
    }

    /**
     * @return array
     */
    public function ToRestArray()
    {
        return array(
            "valueRepresentation"  => $this->getValueRepresentation(),
            "optionRepresentation" => $this->getOptionRepresentation(),
            "inputRepresentation"  => $this->getInputRepresentation(),
            "searchInfo"           => $this->getSearchInfo(),
        );
    }
}
