<?php
/**
  *@package goma form framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see "license.txt"
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 09.05.2012
  * $Version 1.0.9
*/

defined("IN_GOMA") OR die("<!-- restricted access -->"); // silence is golden ;)

class ManyManyDropDown extends MultiSelectDropDown
{		
		/**
		 * the name of the relation of the current field
		 *
		 *@name relation
		 *@access public
		*/
		public $realtion;
		
		/**
		 * field to show in dropdown
		 *
		 *@name showfield
		 *@access public
		*/
		public $showfield;
		
		/**
		 * where clause to filter result in dropdown
		 *
		 *@name where
		 *@access public
		*/
		public $where;
		
		/**
		 *@param string - name
		 *@param string - title
		 *@param array - options
		 *@param array|int - selected items
		 *@param object - parent
		*/
		public function __construct($name = "", $title = null, $showfield = "title", $where = array(), $value = null, $parent = null)
		{
				parent::__construct($name . "ids", $title, $value, $parent);
				$this->relation = strtolower($name);
				$this->showfield = $showfield;
				$this->where = $where;
		}
		
		/**
		 * sets the value if not set
		 *
		 *@name getValue
		 *@access public
		*/
		public function getValue() {
			
			parent::getValue();
			
			if(!$this->dataset) {
				if(is_object($this->form()->result) && (isset($this->form()->result->many_many[$this->relation]) || isset($this->form()->result->belongs_many_many[$this->relation]))) {
					$this->_object = (isset($this->form()->result->many_many[$this->relation])) ? $this->form()->result->many_many[$this->relation] : $this->form()->result->belongs_many_many[$this->relation];
					$this->dataset = call_user_func_array(array($this->form()->result, $this->relation), array())->FieldToArray("versionid");
				} else if(is_object($this->form()->controller)) {
					if(isset($this->form()->controller->modelInst()->many_many[$this->relation]) || isset($this->form()->controller->modelInst()->belongs_many_many[$this->relation])) {
						$this->_object = (isset($this->form()->controller->modelInst()->many_many[$this->relation])) ? $this->form()->controller->modelInst()->many_many[$this->relation] : $this->form()->controller->modelInst()->belongs_many_many[$this->relation];
						$this->dataset = call_user_func_array(array($this->form()->controller->modelInst(), $this->relation), array())->FieldToArray("versionid");
					} else {
						throwError(5, "PHP-Error", "".$this->relation." doesn't exist in this form in ".__FILE__." on line ".__LINE__."");
					}
				} else {
					throwError(5, "PHP-Error", "".$this->relation." doesn't exist in this form in ".__FILE__." on line ".__LINE__."");
				}
			} else {
				if(is_object($this->form()->result) && (isset($this->form()->result->many_many[$this->relation]) || isset($this->form()->result->belongs_many_many[$this->relation]))) {
					$this->_object = (isset($this->form()->result->many_many[$this->relation])) ? $this->form()->result->many_many[$this->relation] : $this->form()->result->belongs_many_many[$this->relation];
				} else if(is_object($this->form()->controller)) {
					if(isset($this->form()->controller->modelInst()->many_many[$this->relation]) || isset($this->form()->controller->modelInst()->belongs_many_many[$this->relation])) {
						$this->_object = (isset($this->form()->controller->modelInst()->many_many[$this->relation])) ? $this->form()->controller->modelInst()->many_many[$this->relation] : $this->form()->controller->modelInst()->belongs_many_many[$this->relation];
						
					} else {
						throwError(5, "PHP-Error", "".$this->relation." doesn't exist in this form in ".__FILE__." on line ".__LINE__."");
					}
				} else {
					throwError(5, "PHP-Error", "".$this->relation." doesn't exist in this form in ".__FILE__." on line ".__LINE__."");
				}
			}
		}
		
		/**
		 * renders the data in the input
		*/
		public function renderInput() {
			$data = DataObject::get($this->_object, array("versionid" => $this->dataset));
			
			if($this->form()->useStateData) {
				$data->setVersion("state");
			}
			
			if($data && $data->count() > 0) {
				$str = "";
				$i = 0;
				foreach($data as $record) {
					if($i == 0) {
						$i++;
					} else {
						$str .= ", ";
					}
					$str .= $record[$this->showfield];
				}
				unset($data, $record, $i);
				return $str;
			} else {
				return lang("form_dropdown_nothing_select", "Nothing Selected");
			}
		}
		
		/**
		 * getDataFromModel
		 *
		 *@param numeric - page
		*/
		public function getDataFromModel($p = 1) {
			
			$data = DataObject::get($this->_object, $this->where);
			$data->activatePagination($p);
			
			if($this->form()->useStateData) {
				$data->setVersion("state");
			}
			
			$arr = array();
			foreach($data as $record) {
				$arr[$record["versionid"]] = convert::raw2text($record[$this->showfield]);
			}			
			$left = ($p > 1);
			
			$right = (ceil($data->_count() / 10) > $p);
			return array("data" => $arr, "left" => $left, "right" => $right);
		}
		
		/**
		 * searches data from the optinos
		 *
		 *@name searchDataFromModel
		 *@param numeric - page
		*/
		public function searchDataFromModel($p = 1, $search = "") {
			$data = DataObject::search_object($this->_object, array($search),$this->where);
			$data->activatePagination($p);
			
			if($this->form()->useStateData) {
				$data->setVersion("state");
			}
			
			$arr = array();
			foreach($data as $record) {
				$arr[$record["versionid"]] = preg_replace('/('.preg_quote($search, "/").')/Usi', "<strong>\\1</strong>", convert::raw2text($record[$this->showfield]));
			}			
			$left = ($p > 1);
			$right = (ceil($data->_count() / 10) > $p);
			return array("data" => $arr, "left" => $left, "right" => $right);
		}
}