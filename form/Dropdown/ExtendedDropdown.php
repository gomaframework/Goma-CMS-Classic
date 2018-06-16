<?php
namespace Goma\Form\Dropdown;

use Convert;
use FormField;

defined("IN_GOMA") or die();

/**
 * This is an extended Searchable Dropdown, which supports:
 * - DataSource
 * - Complex Layouts
 * - Good Searching
 * - Responsive Support
 *
 * It uses selectize as javascript framework.
 *
 * @package Goma\Form\Dropdown
 *
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 */
class ExtendedDropdown extends FormField
{
    /**
     * prefix for session storage.
     */
    const SESSION_CONSTANT = "ExtendedDropdownSessionPart";

    /**
     * @var IDropdownDataSource
     */
    protected $dataSource;

    /**
     * current template.
     */
    public $template = "form/extendedDropdown.html";

    /**
     * @var array
     */
    static $url_handlers = array(
        "search/\$search"  => "search",
        "POST create"      => "createItem"
    );

    /**
     * @var array
     */
    static $allowed_actions = array(
        "search", "createItem"
    );

    /**
     * @param string $name
     * @param string $title
     * @param IDropdownDataSource $dataSource
     * @param null $value
     * @param null $parent
     * @return static
     */
    public static function create($name, $title, $dataSource = null, $value = null, $parent = null)
    {
        return new static($name, $title, $dataSource, $value, $parent);
    }

    /**
     * ExtendedDropdown constructor.
     * @param string $name
     * @param string $title
     * @param IDropdownDataSource $dataSource
     * @param null $value
     * @param null $parent
     */
    public function __construct($name = null, $title = null, $dataSource = null, $value = null, &$parent = null)
    {
        parent::__construct($name, $title, $value, $parent);

        if($name && !$dataSource) {
            throw new \InvalidArgumentException("\$dataSoure must be defined and an IDropdownDataSource.");
        }

        $this->dataSource = $dataSource;
        $this->container->addClass(\ClassManifest::getUrlClassName(self::class));
    }

    /**
     * @param null $fieldErrors
     * @return \FormFieldRenderData
     * @throws \FormInvalidDataException
     */
    public function exportBasicInfo($fieldErrors = null)
    {
        $info = parent::exportBasicInfo($fieldErrors);

        $selectedItems = $this->dataSource->getSelectedValues($this, $this->getModel());
        $selectedItemsArray = $this->convertDataArrayToArray($selectedItems);

        $maxCount = $this->dataSource->maxItemsCount($this);
        $info->customise(
            array(
                "maxItemsCount" => is_null($maxCount) ? null : (int)$maxCount,
                "allowCreate" => !!$this->dataSource->allowCreation($this),
                "allowDrag" => !!$this->dataSource->allowDragnDrop($this),
                "customOptions" => (array)$this->dataSource->extendOptions($this),
                "customJS" => (string)$this->dataSource->jsExtendOptionsBeforeCreate($this),
                "selectedItems" => $selectedItemsArray,
                "selectedItemValues" => array_map(
                    function ($value) {
                        return $value["valueRepresentation"];
                    },
                    $selectedItemsArray
                ),
            )
        );

        $this->templateView->customise(array(
            "selectedItems" => $selectedItemsArray
        ));

        return $info;
    }

    /**
     * @return array|mixed|null|string|\ViewAccessableData
     * @throws \FormInvalidDataException
     */
    public function getModel()
    {
        if (!isset($this->hasNoValue) || !$this->hasNoValue) {
            if($this->POST) {
                if (!$this->isDisabled() && $this->parent && ($postData = $this->parent->getFieldPost($this->PostName())) !== null) {
                    if(($dropdownItemsPost = $this->convertPostDataToDropdownItemsIfPossible($postData)) !== null) {
                        return $this->dataSource->getResultValues($this, $dropdownItemsPost);
                    }
                }
            }
        }

        return parent::getModel();
    }

    /**
     * If possible converts post data to DropdownItems.
     *
     * @var array $data
     * @return array|null null if not possible
     * @throws \FormInvalidDataException
     */
    protected function convertPostDataToDropdownItemsIfPossible($data) {
        $resultDataItems = array();
        if($data && is_array($data) && !\ArrayLib::isAssocArray($data)) {
            // empty field as of selectize
            if($data[0] === "") {
                return array();
            }

            foreach ($data as $item) {
                if($itemObject = $this->getFromCache($item)) {
                    array_push($resultDataItems, $itemObject);
                } else {
                    throw new \LogicException("Selected data item was not found.");
                }
            }

            $maxItems = $this->dataSource->maxItemsCount($this);
            if(is_int($maxItems)) {
                if(count($resultDataItems) > $maxItems) {
                    throw new \FormInvalidDataException($this->name, "Too many items selected for field ".convert::raw2text($this->title).".");
                }
            }

            return $resultDataItems;
        }

        return null;
    }

    /**
     * @return \JSONResponseBody
     */
    public function search() {
        try {
            $page = \RegexpUtil::isNumber($this->getParam("page")) && $this->getParam("page") >= 1 ?
                $this->getParam("page") : 1;
            $perPage = \RegexpUtil::isNumber($this->getParam("perpage")) && $this->getParam("perpage") >= 1 ?
                $this->getParam("perpage") : 50;

            $data = $this->dataSource->getData($this, (string) $this->getParam("search"), $page, $perPage);

            $totalCount = null;
            if(isset($data[0]) && is_array($data[0])) {
                $items = $this->convertDataArrayToArray($data[0]);
                $totalCount = isset($data[1]) && \RegexpUtil::isNumber($data[1]) ? $data[1] : null;
            } else {
                $items = $this->convertDataArrayToArray($data);
                $totalCount = count($items);
            }

            return new \JSONResponseBody(array(
                "total_count" => $totalCount,
                "items"       => $items
            ));
        } catch (\Exception $exception) {
            return new \JSONResponseBody(array(
                "errorcode" => $exception->getCode(),
                "error"     => $exception->getMessage()
            ));
        }
    }

    /**
     * post for creating an item.
     */
    public function createItem() {
        try {
            $result = $this->dataSource->create($this, (string)$this->getParam("input"));
            if(!is_a($result, DropdownItem::class)) {
                throw new \InvalidArgumentException("Wrong return value for datasource->create(). DropdownItem or exception expected.");
            }

            $this->putToCache($result);

            return new \JSONResponseBody($result->ToRestArray());
        } catch (\Exception $e) {
            log_exception($e);

            return new \JSONResponseBody(array(
                "error" => $e->getMessage()
            ));
        }
    }

    /**
     * @param DropdownItem $item
     */
    public function putToCache($item) {
        if(!is_a($item, DropdownItem::class)) {
            throw new \InvalidArgumentException("Only DropdownItems are allowed to put to this cache.");
        }

        \GlobalSessionManager::globalSession()->set(self::SESSION_CONSTANT . "." . $this->form()->name . "." . $this->name . "." . $item->getValueRepresentation(), $item);
    }

    /**
     * @param string $hash
     * @return DropdownItem|null
     */
    public function getFromCache($hash) {
        if(!isset($hash)) {
            return null;
        }

        return \GlobalSessionManager::globalSession()->get(self::SESSION_CONSTANT . "." . $this->form()->name . "." . $this->name . "." . $hash);
    }

    /**
     * @param DropdownItem|DropdownItem[]|null $items
     * @return array
     */
    protected function convertDataArrayToArray($items) {
        if(!isset($items)) {
            return array();
        }

        if(is_a($items, DropdownItem::class)) {
            $items = array($items);
        }

        if(!is_array($items)) {
            throw new \InvalidArgumentException("convertDataArrayToArray: First Parameter must be array, DropdownItem or null.");
        }

        return array_map(function($item){
            /** @var DropdownItem $item */
            if(!strpos(strtolower($item->getSearchInfo()), $this->getParam("search"))) {
                $item->addSearchInfo((string)$this->getParam("search"));
            }

            $this->putToCache($item);

            return $item->ToRestArray();
        }, $items);
    }

    /**
     * @return IDropdownDataSource
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * @param IDropdownDataSource $dataSource
     * @return $this
     */
    public function setDataSource($dataSource)
    {
        $this->dataSource = $dataSource;
        return $this;
    }
}
