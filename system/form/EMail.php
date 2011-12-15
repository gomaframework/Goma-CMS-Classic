<?php
/**
  *@package goma
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2011  Goma-Team
  * last modified: 23.06.2011
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class Email extends FormField 
{
		public function createNode()
		{
				$node = parent::createNode();
				$node->type = "text";
				return $node;
		}
		/**
		 * Validation
		*/
		/**
		 * this function generates some JavaScript for the validation of the field
		 *@name jsValidation
		 *@access public
		*/
		public function jsValidation()
		{
				return '
							var regexp = /^([a-zA-Z0-9\-\._]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z0-9]{2,9})$/;
							if(regexp.test($("#'.$this->ID().'").val()))
							{
									
							} else
							{
									$("#'.$this->divID().'").find(".err").remove();
									$("#'.$this->ID().'").after("<div class=\"err\" style=\"color: #ff0000;\">'.lang("form_email_not_valid", "Please enter a correct E-Mail-Adresse").'</div>");
									return false;
							}
						';
		}
		/**
		 * this is the validation for this field if it is required
		 *@name validation
		 *@access public
		*/
		public function validate($value)
		{
				if(_eregi('^([a-zA-Z0-9\-\._]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z0-9]{2,9})$',$value))
				{
						return true;
				} else
				{
						return lang("form_email_not_valid", "Please enter a correct E-Mail-Adresse");
				}
		}
}