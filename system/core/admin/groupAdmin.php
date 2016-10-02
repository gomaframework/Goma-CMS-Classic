<?php
/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  *@author Goma-Team
  * last modified: 21.10.2013
  * $Version 1.0.4
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class groupAdmin extends adminItem {
	/**
	 * text
	*/
	public $text = '{$_lang_groups}';
	
	/**
	 * permissions
	*/
	public $rights = "canManagePermissions";
	
	/**
	 * sort
	*/
	public $sort = "700";
	
	/**
	 * models
	*/
	public $model = "group";
	
	static $icon = "system/templates/admin/images/group.png";
	
	static $less_vars = "tint-brown.less";

	/**
	 * history-url
	 *
	 * @return string
	 */
	public function historyURL() {
		return "admin/history/group";
	}
	

	/**
	 * logic
	*/
	public function index() {
		
		$config = TableFieldConfig_Editable::create();
		$config->getComponentByType("TableFieldDataColumns")->setDisplayFields(array(
			"id"		=> "ID",
			"users.count" => lang("usercount"),
			"name"		=> lang("name")
		))->setFieldFormatting(array(
			"users.count" => '$users.count ' . lang("usercount")
		));
		$config->removeComponent($config->getComponentByType("TableFieldToolbarHeader"));
		$config->getComponentByType("TableFieldPaginator")->perPage = 20;
		
		$form = new Form($this, "form_groupadmin", array(
			new TableField("groupTable", lang("groups"), $this->modelInst(), $config)
		));
		
		return $form->render();
	}
}
