<?php
/**
  *@package goma
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2011  Goma-Team
  * last modified: 13.07.2011
*/
defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class members extends Page
{
		static public $icon = "images/icons/fatcow-icons/16x16/group.png";
		
		/**
		 *@name name
		*/
		public $name = '{$_lang_mem_members}';
		/**
		 * gets the data for memberlist
		 *
		 *@name member
		 *@access public
		*/
		public function member()
		{
				// online - not yet fully supported
				if(isset($_GET["online"]))
				{
						if($GLOBALS['cms_ajaxbar'] == 1)
						{
								$time_online = $GLOBALS['cms_ajaxbar_timeout'] / 1000 + 2;
						} else
						{
								$time_online = 300;
						}
						$last = TIME - $time_online;
						return DataObject::get("user"," `statistics`.`last_update` > ".dbescape($last)."", array(), array(), array('statistics' => '`statistics`.`user` = `users`.`id`'));
				}
				else
						return DataObject::get("user", array(), array(), array(), array(), null, true);
		}
		public function getForm(&$form)
		{
				parent::getForm($form);
				
				$form->remove("pagecomments");
				$form->remove("rating");
		}
}
class membersController extends PageController
{
		
		public $url_handlers = array(
			'$id!'				   	=> 'showmember',
			'$Action/$id/$otherid' 	=> '$Action'
		);
		
		public $allowed_actions = array(
			"showmember"
		);
		/**
		 * template of this controller
		 *@var string
		*/
		public $template = "account/memberlist.html";
		/**
		 * shows a specific member
		 *
		 *@name showmember
		 *@�ccess public
		*/
		public function showmember()
		{
				
				$id = $this->request->getParam("id");
				$userdata = DataObject::get("user", array("id" => $id));
				
				return Object::instance("ProfileController")->index($id);
		}
}
