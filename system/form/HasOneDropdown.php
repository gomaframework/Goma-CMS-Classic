<?php use Goma\Form\Dropdown\ExtendedDropdown;
use Goma\Form\Dropdown\HasOneDropdownDataSource;

defined("IN_GOMA") OR die();

/**
 * This is a simple searchable dropdown, which can be used to select has-one-relations.
 *
 * It supports has-one-realtions of DataObjects and just supports single-select.
 *
 * @package     Goma\Form
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.4.1
 */
class HasOneDropdown extends ExtendedDropdown
{

	/**
	 * @param string $name
	 * @param string $title
	 * @param string $showField
	 * @param array $where
	 * @param string $value
	 * @param Form|null $parent
	 * @return HasOneDropdown
	 */
	public static function create($name, $title, $showField = "title", $where = array(), $value = null, $parent = null)
	{
		return new self($name, $title, $showField, $where, $value, $parent);
	}

	public static function createWithInfoField($name, $title, $showField = "title", $infoField = null, $where = array(), $value = null, $parent = null) {
		$field = self::create($name, $title, $showField, $where, $value, $parent);

		$field->info_field = $infoField;

		return $field;
	}

    /**
     * HasOneDropdown constructor.
     * @param string $name
     * @param null $title
     * @param string $showfield
     * @param array $where
     * @param null $value
     * @param null $parent
     */
	public function __construct($name = "", $title = null, $showfield = "title", $where = array(), $value = null, &$parent = null)
	{
	    $dataSource = new HasOneDropdownDataSource(false, $showfield);
	    $dataSource->setFilter($where);

		parent::__construct($name, $title, $dataSource, $value, $parent);

        $this->dbname = $this->dbname . "id";
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

        /** @var HasOneDropdownDataSource $source */
        $source = $this->dataSource;
        $source->setUseStateData($this->useStateData);

        return $info;
    }

    /**
     * @param $name
     * @param $value
     */
	public function __set($name, $value)
    {
        /** @var HasOneDropdownDataSource $source */
        $source = $this->dataSource;
        if($name == "where") {
            $source->setFilter($value);
        }

        if($name == "info_field") {
            $source->setInfoField($value);
        }

        $this->$name = $value;
    }
}
