<?php
/**
  *@package goma cms
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 09.02.2012
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class settingsAdmin extends adminItem
{
		// config
		public $text = '{$_lang_settings}';
		
		public $sort = 980;
		
		public $rights = "SETTINGS_ADMIN";
		
		public $models = array("newsettings");
		
		public $template = "admin/settings.html";

		
		public function Form() {
			$data = DataObject::get("newsettings", array("id" => 1))->first();
			return parent::Form(null, $data);
		}
		
		public function submit_form($data, $form, $model = null) {
			$status = (SITE_MODE == STATUS_DISABLED) ? STATUS_DISABLED : $data["status"]; 
			writeProjectConfig(array('lang' => $data["lang"], "status" => $status, "timezone" => $data["timezone"], "date_format" => $data["date_format"]));
			parent::safe($data, $form, $model);
		}
}