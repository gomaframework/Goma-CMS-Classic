<?php
/**
  * this class allows the view (template) access to the class, which extends this
  * it provides methods to do e.g. $object["test"] = 1;
  * the following features are implemented
  * iterator - foreach
  * array-access 
  * overloading properties
  *@package goma
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 31.08.2012
  * $Version 2.2
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class ViewAccessableData extends Object implements Iterator, ArrayAccess
{
		/**
		 * if this is set to true or false convert in offsetGet will be edited
		 *
		 * it's for IF-Clauses
		 *@name convertDefault
		 *@access public
		*/
		public $convertDefault = null;
		
		/**
		 * data
		 *
		 *@name data
		 *@acccess protected
		 *@var array
		*/
		public $data = array();
		
		/**
		 * contains the original data
		 *
		 *@name original
		 *@access public
		*/
		public $original = array();
		
		/**
		 * default castings
		 *@name defaultCasting
		 *@access public
		 *@var string
		*/
		public static $default_casting = "Varchar";
		
		/**
		 * customised data
		 *@name customised
		 *@access protected
		 *@var array
		*/
		public $customised = array();
		
		/**
		 * dataset-position
		 *
		 *@name dataSetPosition
		 *@access public
		*/
		public $dataSetPosition = 0;
		
		/**
		 * casting
		 *
		 *@name casting
		*/
		public $casting = array();
		
		/**
		 * indicates whether the data was changes or not
		 *
		 *@name changed
		 *@access public
		*/
		public $changed = false;
		
		/**
		 * dataset in which this DataObject is
		 *
		 *@name dataset
		 *@access public
		*/
		public $dataset;
		
		/**
		 * dataClass
		 *
		 *@name dataClass
		*/
		public $dataClass;
		
		/**
		 * generates casting
		 *
		 *@name generateCasting
		 *@access public
		*/
		public function generateCasting() {
			$casting = (array) $this->casting;
			foreach($this->LocalcallExtending("casting") as $_casting) {
				$casting = array_merge($casting, $_casting);
				unset($_casting);
			}
			
			$parent = get_parent_class($this);
			if($parent != "viewaccessabledata" && !ClassInfo::isAbstract($parent)) {
				$casting = array_merge(Object::instance($parent)->generateCasting(), $casting);
			}
			
			$casting = ArrayLib::map_key("strtolower", $casting);
			return $casting;
		}
		
		/**
		 * a list of not allowed methods
		 *@name notViewableMethods
		 *@access public
		 *@var array
		*/
		public static $notViewableMethods = array
		(
			"getdata",
			"getform",
			"geteditform",
			"getwholedata",
			"set_many_many",
			"get_has_one",
			"get_many",
			"get",
			"setField",
			"setwholedata",
			"write",
			"writerecord",
			"__construct",
			"method_exists",
			"callmethodbyrecord",
			"getmanymany",
			"gethasmany",
			"search",
			"where",
			"fields",
			"getoffset",
			"getversion",
			"_get",
			"getObject"
		);
		/**
		 * defaults
		*/
		public $defaults = array();
		/**
		 * construct
		 *@name __construct
		 *@param array - wholedata
		 *@param numeric position
		*/
		public function __construct($data = null)
		{
				parent::__construct();
				
				$this->dataClass = $this->class;
				
				/* --- */
				
				if(isset($data)) { 
					$this->data = ArrayLib::map_key("strtolower", (array)$data);
					$this->original = $this->data;
				}
				
				if(isset(ClassInfo::$class_info[$this->class]["casting"]))
					$this->casting = ClassInfo::$class_info[$this->class]["casting"];
		}
		/**
		 * this function returns the current record as an array
		 *@name ToArray
		 *@access public
		 *@param array - extra fields, which are not in database
		*/
		public function ToArray($additional_fields = array())
		{
				if(empty($additional_fields))
						return $this->data;
				else
				{
						$data = $this->data;
						foreach($additional_fields as $field)
						{
								$data[$field] = $this[$field];
						}
						return $data;
				}
		}

		/**
		 * data layer
		 *
		 *@name fieldGet
		 *@access public
		*/
		public function fieldGet($name) {
			$name = trim(strtolower($name));
			if(isset($this->data[$name])) 
				return $this->data[$name];
			else if (isset($this->defaults[$name]))
				return $this->defaults[$name];
			else
				return null;
		}
		
		/**
		 * is field
		 *
		 *@name isField
		 *@access public
		*/
		public function isField($name) {
			$name = trim(strtolower($name));
			
			return (isset($this->data[$name]) || isset($this->defaults[$name]));
		}
		
		/**
		 * sets the field
		 *
		 *@name setField
		 *@access public
		*/
		public function setField($name, $value) {
			$this->changed = true;
			$this->setOffset($name, $value);
		}
				
		/**
		 * Overloading with __get and __set and __call
		 *@link http://www.php.net/manual/en/language.oop5.magic.php
		*/

		/**
		 * new get method
		 *
		 *@name __get
		 *@access public
		*/
		public function __get($offset) {
			// third call
			return $this->getOffset($offset);
		}
		
		/**
		 * new set method
		 *
		 *@name __set
		 *@access public
		*/
		public function __set($name, $value) {
			$this->changed = true;
			$name = strtolower(trim($name));
			
			if($this->isSetMethod($name))
			{
					$this->callSetMethod($name, $value);
			} else
			{
					$this->setOffset($name, $value);
			}
		}
		
		/**
		 * new set method
		 *@name setOffset
		 *@access public
		 *@param string - offset
		 *@param mixed - value
		*/
		public function setOffset($var, $value)
		{
				if(is_array($this->data))
				{
						// first unset, so the new value is last value of data stack 
						unset($this->data[$var]);
						$this->data[$var] = $value;
				} else
				{
						$this->data = array($var => $value);
				}
				$this->changed = true;
		}
		/**
		 * gets the offset
		 *
		 *@name getOffset
		 *@access public
		 *@param string - name
		 *@param array - args
		*/
		public function getOffset($name, $args = array()) {
			
			
			if(PROFILE) Profiler::mark("ViewAccessableData::getOffset");
			
			$lowername = strtolower($name);
			
			if($lowername == "count")
				return $this->_count();
			
			
			if(isset($this->customised[$lowername])) {
				$data = $this->customised[$lowername];
			// methods
			} else if(Object::method_exists($this->class, $name)) {
				if(PROFILE) Profiler::unmark("ViewAccessableData::getOffset");
				return parent::__call($name, $args);
			} else
			
			// methods
			if($this->isOffsetMethod($lowername)) {
				$data = $this->callOffsetMethod($lowername, $args);
			} else
			
			// data
			
			if(isset($this->data[$lowername])) {
				$data = $this->data[$lowername];
			} else 
			
			if($this->isServer($name, $lowername)) {
				$data = $this->serverGet($name, $lowername);
			}
			
			if(isset($data)) {
				// casting-array
				if(is_array($data) && isset($data["casting"], $data["value"])) {
					$data = DBField::convertByCasting($data["casting"], $lowername, $data["value"]);
				}
				
				if(is_array($data))
					$data = new ViewAccessableData($data);
				
				unset($lowername, $name);
				if(PROFILE) Profiler::unmark("ViewAccessableData::getOffset");
				
				return $data;
			} else {
				unset($lowername, $name);
				/*if(DEV_MODE) {
					$trace = debug_backtrace();
					if(isset($trace[1]['file']))
						logging('Warning: Call to undefined method ' . $this->class . '::' . $name . ' in '.$trace[1]['file'].' on line '.$trace[1]['line']);
					else {
						logging('Warning: Call to undefined method ' . $this->class . '::' . $name . '');
					}
					
				}*/
				if(PROFILE) Profiler::unmark("ViewAccessableData::getOffset");
				return null;
			}
		}
		
		/**
		 * forces making an object of the given data
		 *
		 *@name makeObject
		 *@access public
		*/
		public function makeObject($name, $data, $cachename = null) {
			if(PROFILE) Profiler::mark("ViewAccessableData::makeObject");
			
			if(!isset($cachename))
				$cachename = "1_" . $name;
			
			// if is already an object
			if(is_object($data)) {
			
				if(PROFILE) Profiler::unmark("ViewAccessableData::makeObject");
				return $data;
			
			// casting-array
			} else if(is_array($data) && isset($data["casting"], $data["value"])) {
			
				$object = DBField::getObjectByCasting($data["casting"], $name, $data["value"]);
				if(PROFILE) Profiler::unmark("ViewAccessableData::makeObject");
				return $object;
			
			// if is array, get as array-object
			} else if(is_array($data)) {
				$object = new ViewAccessAbleData($data);
				if(PROFILE) Profiler::unmark("ViewAccessableData::makeObject");
				return $object;
			
			// default object
			} else {
				if(isset($this->casting[$name])) {
					$object = DBField::getObjectByCasting($this->casting[$name], $name, $data);
				} else {
					$c = ClassInfo::getStatic("viewaccessabledata", "default_casting");
					$object = new $c($name, $data);
				}
				
				if(PROFILE) Profiler::unmark("ViewAccessableData::makeObject");
				return $object;
			}
		}
		
		
		/**
		 * checks if object exists
		 *
		 *@name isOffset
		 *@access public
		*/
		final public function isOffset($offset) {
			return $this->__cancall($offset);
		}
		
		/**
		 * new call method
		 *
		 *@name __call
		 *@access public
		*/
		public function __call($name, $args) {
			$name = trim($name);
			$lowername = strtolower($name);
			
			return $this->makeObject($lowername, $this->getOffset($name, $args));
		}
		
		/**
		 * checks if there is a method get + $name or $name
		 *
		 *@name isOffsetMethod
		 *@access public
		 *@param string - name
		*/
		public function isOffsetMethod($name) {
			return (!in_array("get" . $name, self::$notViewableMethods) && Object::method_exists($this->class, "get" . $name));
		}
		
		/**
		 * checks if there is a method get + $name or $name
		 * and calls it
		 *
		 *@name callOffsetMethod
		 *@access public
		 *@param string - name
		 *@param array - args
		*/
		public function callOffsetMethod($name, $args) {
			if(!in_array("get" . $name, self::$notViewableMethods) && Object::method_exists($this->class, "get" . $name)) {
				return call_user_func_array(array($this, "get" . $name), $args);
			} 
			
			return false;
		}
		/**
		 * new __cancall
		 *
		 *@access public
		 *@param string - name
		*/
		public function __cancall($name) {
			$name = trim($name);
			$lowername = strtolower($name);
			
			
			//  methods
			if($this->isOffsetMethod($lowername)) {
				return true;
			} else
			
			if(isset($this->customised[$lowername])) {
				return true;
			} else
			
			// server
			if($this->isServer($name, $lowername)) {
				return true;
			} else
			// data
			
			if(isset($this->data[$lowername])) {
				return true;
			}

			return false;
		}
		/**
		 * checks if a method "set" . $offset exists
		 *@name isSetMethod
		 *@access public
		 *@param string - offset
		*/
		public function isSetMethod($offset)
		{
				return (self::method_exists($this, "set" . $offset) && !in_array(strtolower("set" . $offset), self::$notViewableMethods));
		}
		/**
		 * calls a method "set" . $offset
		 *@name callSetMethod
		 *@access public
		 *@param string - offset
		 *@param mixed - value
		*/
		public function callSetMethod($offset, $value)
		{
				$func = "set" . $offset;
				return call_user_func_array(array($this, $func), array($value));
		}
				/* Server-vars */
		/**
		 * checks if Server-var
		 *@name isServer
		 *@access public
		 *@param string - offset
		*/
		public function isServer($offset, $lowerOffset)
		{
				
				if(substr($lowerOffset, 0, 8) == "_server_")
				{
					$key = substr($offset, 8);
					if(strtolower($key) == "redirect" || strtolower($key) == "redirect_parent") {
						return true;
					}
					return isset($_SERVER[$key]);
				} else if(substr($lowerOffset, 0, 6) == "_post_") {
					$key = substr($offset, 6);
					return isset($_POST[$key]);
				} else if (substr($lowerOffset, 0, 5) == "_get_") {
					$key = substr($offset, 5);
					return isset($_GET[$key]);
				} else {
					return false;
				}
		}
		/**
		 * gets server-var
		 *@name ServerGet
		 *@access public
		 *@param string - offset
		*/
		public function ServerGet($offset, $loweroffset)
		{
				
				if(substr($loweroffset, 0, 8) == "_server_")
				{
					
					$key = substr($offset, 8);
					if(strtolower($key) == "redirect") {
						return getredirect();
					} else if(strtolower($key) == "redirect_parent") {
						return getredirect(true);
					}
					
					if(strtolower($key) == "request_uri") {
						if(Core::is_ajax() && isset($_SERVER["HTTP_X_REFERER"])) {
							return $_SERVER["HTTP_X_REFERER"];
						}
					}
					return $_SERVER[$key];
				} else if(substr($loweroffset, 0, 6) == "_post_") {
					$key = substr($offset, 6);
					return $_POST[$key];
				} else if (substr($loweroffset, 0, 5) == "_get_") {
					$key = substr($offset, 5);
					return $_GET[$key];
				} else {
					return false;
				}
		}
		

		/**
		 * gets the offset
		 *@name offsetGet
		*/
		public function offsetGet($offset)
		{
				return $this->__get($offset);
				
		}
				/**
		 * sets the offset
		 *@name offsetSet
		*/
		public function offsetSet($offset, $value)
		{
				
				return $this->__set($offset, $value);
		}
		/**
		 * checks if the offset exists
		 *@name offsetExists
		*/
		public function offsetExists($offset)
		{
			// third call
			return Object::method_exists($this, $offset);
		}
		/**
		 * unsets a offset
		 * in this object it do nothing
		 *@name offsetUnset
		*/
		public function offsetUnset($offset)
		{
				// do nothing
		}
		
		/**
		 * gets offset as object
		 *@name doObject
		 *@param string - name of offset
		*/
		public function doObject($offset)
		{		
				return $this->__call($offset, array());
		}
		
		
		
		/**
		 * to cutomise this data with own data for loops
		 *@name customise
		 *@access public
		 *@param array - data for loops
		 *@param array - replacement-data
		*/
		public function customise($loops = array(), $loops_2 = array())
		{
				if(!empty($loops_2))
					$loops = array_merge($loops, $loops_2);
					
				$loops = Arraylib::map_key($loops, "strtolower");
				$this->customised = array_merge($this->customised, $loops);
				
				return $this;
		}
		
		/**
		 * iterator
		 * this extends this dataobject to use foreach on it
		 * @link http://php.net/manual/en/class.iterator.php
		*/
		/**
		 * this var is the current position
		 *@name position
		 *@access public
		*/
		private $position = 0;
		/**
		 * rewind $position to 0
		 *@name rewind
		*/
		public function rewind()
		{
			if(is_array($this->data)) {
				reset($this->data);
			}
			$this->position = 0;
		}
		/**
		 * check if data exists
		 *@name valid
		*/
		public function valid()
		{
				return ($this->position < count($this->data));
		}
		/**
		 * gets the key
		 *@name key
		*/
		public function key()
		{
				return key($this->data);
		}
		/**
		 * gets the next one
		 *@name next
		*/
		public function next()
		{
				
				$this->position++;
				next($this->data);
		}
		/**
		 * gets the current value
		 *@name current
		*/
		public function current()
		{
				$data = current($this->data);
				if(is_array($data))
					$data = new ViewAccessAbleData($data);
				
				return $data;
		}
		/**
		 * sets the position of the array
		 *
		 *@name setPosition
		 *@access public
		*/
		public function setPosition($pos) {
			if($pos < count($this->data) && $pos > -1) {
				$this->position = $pos;
				if((count($this->data) / 2) < $pos) {
					end($this->data);
					$i = count($this->data);
					while($i > $pos) {
						prev($this->data);
						$i--;
					}
				} else {
					reset($this->data);
					$i = 0;
					while($i < $pos) {
						next($this->data);
						$i++;
					}
				}
			}
		}
		/**
		 * resets the data
		 *@name reset
		 *@access public
		*/
		public function reset()
		{
				$this->data = false;
				$this->position = 0;
				$this->customised = array();
		}
		/**
		 * some functions for the template
		*/
		
		/**
		 * returns this for <% CONTROL this() %>
		 *@name this
		 *@access public
		*/
		public function this()
		{
			return $this;
		}
		
		/**
		 * renders a view with the data of this DataObject
		 *@name renderWith
		 *@access public
		 *@param string - template
		 *@param array - areas
		 *@param expansion-name of you want to use the expansion-path too
		*/
		public function renderWith($view, $areas = array(), $expansion = null)
		{
				return tpl::render($view,array(), $this, $areas, $expansion);
		}
		/**
		 * bool - for IF in template
		 *
		 *@name toBool
		 *@access public
		*/
		public function bool() {
			return (count($this->data) > 0);
		}
		
		/**
		 * returns if this is the first entry or not
		 *@name first
		 *@access public
		*/
		public function first()
		{	
				return ($this->dataSetPosition == 0);
		}
		
		/**
		 * returns if this is the last entry or not
		 *@name last
		 *@access public
		*/
		public function last()
		{	
				return ($this->dataSetPosition + 1 == $this->dataset->count());
		}
		
		/**
		 * returns current position
		*/
		public function position() {
			return $this->dataSetPosition;
		}
		
		/**
		 * returns if this is a highlighted one
		 *
		 *@name highlight
		 *@access public
		*/
		public function highlight() {
			$r = ($this->dataSetPosition + 1) % 2;
			return ($r == 0);
		}
		
		/**
		 * returns if this is a white one
		 *
		 *@name white
		 *@access public
		*/
		public function white() {
			return (!$this->highlight());
		}
		/**
		 * make the functions on top to variables, for example $this.white
		*/
		public function getWhite() {
			return $this->white();
		}
		
		public function getHighlight() {
			return $this->highlight();
		}
		
		public function isFirst() {
			return $this->first();
		}
		
		public function isLast() {
			return $this->last();
		}
		
		public function getPosition() {
			return $this->dataSetPosition;
		}
		
		/**
		 * returns if the record was changed
		 *
		 *@name wasChanged
		 *@access public
		*/
		public function wasChanged() {
			return $this->changed;
		}
		
		/**
		 * sets the value of changed
		 *
		 *@name setChanged
		 *@access public
		 *@param bool
		*/
		public function setChanged($val) {
			if(is_bool($val))
				$this->changed = $val;
		}
		
		/**
		 * deprecated method, please use if($object) instead of if($object->_count() > 0)
		 *
		 *@name _count
		 *@access public
		*/
		public function _count() {
			if(isset($this->data["count"]))
				return $this->data["count"];
			
			Core::deprecate(2.0);
			return 1;
		}
		
		/**
		 * gets a cloned object
		 *
		 *@name _clone
		 *@access public
		*/
		public function _clone() {
			return clone $this;
		}
		
}