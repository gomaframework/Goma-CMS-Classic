<?php
/**
  *@package goma
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2011  Goma-Team
  * last modified: 15.06.2011
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class HTMLField extends FormField 
{
		public function __construct($name, $html = null, $form = null)
		{
				parent::__construct($name, null, null, $form);
				$this->html = $html;
		}
		public function field()
		{
				$this->callExtending("beforeField");
				
								
				$this->container->append($this->html);
				
				if($this->html == "" || strlen($this->html) < 15) {
					$this->container->addClass("hidden");
				}
				
				$this->callExtending("afterField");
				
				return $this->container;
		}
}