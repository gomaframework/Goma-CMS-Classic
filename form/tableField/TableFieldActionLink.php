<?php
defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)
/**
 * This component is creating a link in the Actions-Columns of each row in the TableField.
 * It is based on the template form/tableField/actionLink.html
 * $destination, $html, $titleAttribute and $classes are variables in the form.
 *
 * @package     Goma\Form-Framework\TableField
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.0.2
 */
class TableFieldActionLink implements TableField_ColumnProvider {

	/**
	 * description.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * @var string
	 */
	protected $html;

	/**
	 * @var bool|mixed
	 */
	protected $requirePerm;

	/**
	 * @var null|string
	 */
	protected $titleAttribute;

	/**
	 * @var array
	 */
	protected $classes;

	/**
	 * Constructor.
	 *
	 * @param   string $destination href of the link. It is possible to use basic variables, which are replaced with model-values of the row.
	 * @param   string $html HTML between the a-tags, it can be simple text as well as an icon
	 * @param   string $titleAttribute  the title-attribute of the a-tag.
	 * @param   callable|string|boolean $requirePerm
     *          if this is callable, the method is called and only if it returns true the action will be added.
     *          if this is a string, $record->can($requirePerm) will be called and only if it returns true, the action will be added.
     *          if this is boolean or null, nothing happens.
	 * @param array $classes css-classes
	 */
	public function __construct($destination, $html, $titleAttribute = null, $requirePerm = false, $classes = array()) {
		$this->destination = $destination;
		$this->html = $html;
		$this->requirePerm = $requirePerm;
		$this->titleAttribute = $titleAttribute;
		$this->classes = $classes;
	}
	
	
	/**
	 * Add a column 'Actions'.
	 * 
	 * @param TableField $tableField
	 * @param array $columns 
	 */
	public function augmentColumns($tableField, &$columns) {
		if(!in_array('Actions', $columns))
			$columns[] = 'Actions';
	}
	
	/**
	 * Return any special attributes that will be used for the column.
	 *
	 * @param TableField $tableField
	 * @param DataObject $record
	 * @param string $columnName
	 *
	 * @return array
	 */
	public function getColumnAttributes($tableField, $columnName, $record) {
		return array('class' => 'col-buttons');
	}
	
	/**
	 * Add the title.
	 * 
	 * @param TableField $tableField
	 * @param string $columnName
	 *
	 * @return array
	 */
	public function getColumnMetadata($tableField, $columnName) {
		if($columnName == 'Actions') {
			return array('title' => '');
		}
	}
	
	/**
	 * Which columns are handled by this component.
	 * 
	 * @param TableField $tableField
	 *
	 * @return array
	 */
	public function getColumnsHandled($tableField) {
		return array('Actions');
	}
	
	/**
	 * generates the content of the column "Actions".
	 *
	 * @param TableField $tableField
	 * @param DataObject $record
	 * @param string $columnName
	 *
	 * @return string - the HTML for the column 
	 */
	public function getColumnContent($tableField, $record, $columnName) {
		if(is_string($this->requirePerm) || is_callable($this->requirePerm)) {
			if(is_callable($this->requirePerm)) {
				if(!call_user_func_array($this->requirePerm, array($tableField, $record)))
					return;
			} else if(!$record->can($this->requirePerm)){
				return;
			}
		}

		// required for parsing variables
		$data = $record;
		
		// format innerhtml
		$format = str_replace('"', '\\"', $this->html);
		$format = preg_replace_callback('/\{?\$([a-zA-Z0-9_][a-zA-Z0-9_\-\.]+)\.([a-zA-Z0-9_]+)\((.*?)\)}?/si', array("TableFieldDataColumns", "convert_vars"), $format);
		$format = preg_replace_callback('/\$([a-zA-Z0-9_][a-zA-Z0-9_\.]+)/si', array("TableFieldDataColumns", "vars"), $format);
		
		eval('$value = "' . $format . '";');

		// format destination
		$formatDestination = str_replace('"', '\\"', $this->destination);
		$formatDestination = preg_replace_callback('/\{?\$([a-zA-Z0-9_][a-zA-Z0-9_\-\.]+)\.([a-zA-Z0-9_]+)\((.*?)\)}?/si', array("TableFieldDataColumns", "convert_vars"), $formatDestination);
		$formatDestination = preg_replace_callback('/\$([a-zA-Z0-9_][a-zA-Z0-9_\.]+)/si', array("TableFieldDataColumns", "vars"), $formatDestination);
		eval('$destination = "' . $formatDestination . '";');
		
		// format title
		$formatTitle = str_replace('"', '\\"', $this->titleAttribute);
		$formatTitle = preg_replace_callback('/\{?\$([a-zA-Z0-9_][a-zA-Z0-9_\-\.]+)\.([a-zA-Z0-9_]+)\((.*?)\)}?/si', array("TableFieldDataColumns", "convert_vars"), $formatTitle);
		$formatTitle = preg_replace_callback('/\$([a-zA-Z0-9_][a-zA-Z0-9_\.]+)/si', array("TableFieldDataColumns", "vars"), $formatTitle);
		eval('$title = "' . $formatTitle . '";');

		// format title
		$formatInner = str_replace('"', '\\"', $this->html);
		$formatInner = preg_replace_callback('/\{?\$([a-zA-Z0-9_][a-zA-Z0-9_\-\.]+)\.([a-zA-Z0-9_]+)\((.*?)\)}?/si', array("TableFieldDataColumns", "convert_vars"), $formatInner);
		$formatInner = preg_replace_callback('/\$([a-zA-Z0-9_][a-zA-Z0-9_\.]+)/si', array("TableFieldDataColumns", "vars"), $formatInner);
		eval('$inner = "' . $formatInner . '";');
		
		
		$data = new ViewAccessableData();
		/** @var string $destination */
		$data->setField("destination", $destination);
		/** @var string $inner */
		$data->setField("html", $inner);
		/** @var $title $title */
		$data->setField("titleAttribute", $title);
		$data->setField("classes", implode(" " , (array) $this->classes));
		
		return $data->renderWith("form/tableField/actionLink.html");
	}
}
