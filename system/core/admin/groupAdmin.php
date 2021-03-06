<?php defined("IN_GOMA") OR die();

/**
 * Group-Admin-Panel
 *
 * @package goma framework
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 * @version 1.1
 */
class groupAdmin extends adminItem {
	/**
	 * text
	 */
    static $text = '{$_lang_groups}';

	/**
	 * permissions
	 */
    static $rights = "canManagePermissions";

	/**
	 * sort
	 */
	static $sort = "700";

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
			"Name"		=> lang("name")
		))->setFieldFormatting(array(
			"users.count" => '<a href="admin/user/?groupid=$id&redirect='.urlencode(ROOT_PATH  . $this->namespace . URLEND).'">$users.count ' . lang("usercount") . '</a>'
		));
		$config->removeComponent($config->getComponentByType("TableFieldToolbarHeader"));
		$config->getComponentByType("TableFieldPaginator")->perPage = 20;

		$this->callExtending("extendGroupAdmin", $config);

		$form = new Form($this, "form_groupadmin", array(
			new TableField("groupTable", lang("groups"), $this->modelInst(), $config)
		));

		return $form->render();
	}
}
