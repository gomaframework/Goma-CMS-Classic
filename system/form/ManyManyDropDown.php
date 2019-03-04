<?php use Goma\Form\Dropdown\ExtendedDropdown;
use Goma\Form\Dropdown\ManyManyDropdownDataSource;

defined("IN_GOMA") OR die();

/**
 * This is a simple searchable dropdown, which can be used to select many-many-connections.
 *
 * It supports many-many-relations of DataObjects and MultiSelecting.
 *
 * @property bool sortable
 * @package     Goma\Form
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.5.1
 */
class ManyManyDropDown extends ExtendedDropdown
{
	/**
	 * @param string $name
	 * @param string $title
	 * @param string $showfield
	 * @param array $where
	 * @param string $value
	 * @param Form $parent
	 */
	public function __construct($name = "", $title = null, $showfield = "title", $where = array(), $value = null, $parent = null)
	{
        $dataSource = new ManyManyDropdownDataSource(false, $showfield);
        $dataSource->setFilter($where);

        parent::__construct($name, $title, $dataSource, $value, $parent);

        $this->dbname = $this->dbname . "ids";
        $this->placeholder = str_replace("%label%", $title, lang("form_select_x"));
	}

    /**
     * @param null $fieldErrors
     * @return FormFieldRenderData
     * @throws FormInvalidDataException
     */
    public function exportBasicInfo($fieldErrors = null)
    {
        $info = parent::exportBasicInfo($fieldErrors);

        /** @var ManyManyDropdownDataSource $source */
        $source = $this->dataSource;
        $source->setUseStateData($this->isStateData());

        return $info;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        /** @var ManyManyDropdownDataSource $source */
        $source = $this->dataSource;
        if($name == "where") {
            $source->setFilter($value);
        }

        if($name == "info_field") {
            $source->setInfoField($value);
        }

        if($name == "sortable") {
            $source->setSortable(!!$value);
        }

        $this->$name = $value;
    }

    public function __get($name)
    {
        /** @var ManyManyDropdownDataSource $source */
        $source = $this->dataSource;
        if($name == "where") {
            return $source->getFilter();
        }

        if($name == "info_field") {
            return $source->getInfoField();
        }

        if($name == "sortable") {
            return $source->isSortable();
        }

        return parent::__get($name);
    }
}
