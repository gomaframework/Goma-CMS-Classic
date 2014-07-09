<?php
/**
  *@package goma cms
  *@link http://goma-cms.org
  *@license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  *@author Goma-Team
  * last modified: 25.11.2012
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
		
		static $icon = "templates/images/settings.png";
		
		/**
		 * history-url
		 *
		 *@name historyURL
		 *@access public
		*/
		public function historyURL() {
			return "admin/history/newsettings";
		}
		
		/**
		 * generates the form
		 *
		 *@name Form
		*/
		public function Form() {
			Resources::$lessVars = "tint-blue.less";

			$data = DataObject::get("newsettings", array("id" => 1))->first();
			return parent::Form(null, $data);
		}
		
		/**
		 * writes correct settings to correct location
		 *
		 *@name submit_form
		*/
		public function submit_form($data, $form, $model = null) {
			if(isset($data["lang"], $data["status"], $data["timezone"], $data["date_format_date"])) {
				if(!file_exists(ROOT . LANGUAGE_DIRECTORY . $data["lang"])) {
					throwError(6, "Invalid-Error", "Selected language not existing!");
				}
				$status = (SITE_MODE == STATUS_DISABLED) ? STATUS_DISABLED : $data["status"]; 
				writeProjectConfig(array('lang' => $data["lang"], "status" => $status, "timezone" => $data["timezone"], "date_format_date" => $data["date_format_date"], "date_format_time" => $data["date_format_time"]));
			} else {
				throwError(6, "Invalid-Error", "Too less keys in data to write settings.");
			}
			parent::safe($data, $form, $model);
		}
		
		/**
		 * returns an array of the wiki-article and youtube-video for this controller
		 *
		 *@name helpArticle
		 *@access public
		*/
		public function helpArticle() {
			return array("wiki" => "Einstellungen");
		}
}