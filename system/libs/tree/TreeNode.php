<?php defined("IN_GOMA") OR die();

/**
 * @package		Goma\Tree-Lib
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version		1.0
 */

class TreeNode extends ArrayList {
	/**
	 * unique id of the node.
	 *
	 * @var string
	*/
	public $nodeid;
	
	/**
	 * record-id of the node. this should be unique, too, but is not absolutily required.
	 *
	 *@var int
	*/
	public $recordid;
	
	/**
	 * text of the node.
	*/
	public $title;
	
	/**
	 * class-name of the tree-node.
	*/
	public $treeclass;
	
	/**
	 * icon of this tree-node.
	*/
	public $icon;
	
	/**
	 * defines the behaviour of children.
	 *
	 * force on state for children:
	 * - open or closed
	 * null for automatic
	*/
	public $childState;
	
	/**
	 * you can put the model-connection here.
	 *
	 * @param 	object
	*/
	public $model;
	
	/**
	 * bubbles with different colors.
	 * for example: Modified, Submitted
	*/
	protected $bubbles = array();
	
	/**
	 * stores the callback, which is called when children are needed.
	*/
	protected $childCallback;
	
	/**
	 * child-params.
	*/
	protected $childParams;
	
	/**
	 * html-classes.
	*/
	protected $htmlClasses = array();
	
	/**
	 * the callback over which this node is rendered.
	*/
	protected $linkCallback;
	
	/**
	 * generates a new treenode.
	 *
	 * @param 	string $nodeid id of this node
	 * @param 	int $recordid
	 * @param 	string $title text of this node
	 * @param 	string $class_name class-name for this node
	*/
	public function __construct($nodeid = null, $recordid = null, $title = null, $class_name = null, $icon = null) {
		
		parent::__construct(null);
		
		if(!isset($nodeid)) {
			return false;
		}
			
		
		$this->nodeid = $nodeid;
		$this->recordid = $recordid;
		$this->title = $title;
		$this->treeclass = $class_name;
		if(isset($icon) && $icon && $icon = ClassInfo::findFile($icon, $class_name)) {
			$this->icon = $icon;
		} else if(strtolower($class_name) != "treeholder" && !empty($class_name)) {
			$this->icon = ClassInfo::getClassIcon($class_name);
		}
	}
	
	/**
	 * returns the icon
	 *
	 *@name getIcon
	 *@access public
	*/
	public function getIcon() {
		return $this->icon;
	}
	
	/**
	 * sets an icon
	 *
	 *@name setIcon
	 *@access public
	*/
	public function setIcon($icon) {
		if($icon && $icon = ClassInfo::findFile($icon)) {
			$this->icon = $icon;
			return true;
		}
		
		return false;
	}
	
	/**
	 * adds a bubble
	 *
	 *@name addBubble
	 *@access public
	 *@param text
	 *@param color: green, yellow, red, blue, grey, orange, purple
	*/
	public function addBubble($text, $color = "blue") {
		
		$this->bubbles[md5($text)] = array("text" => $text, "color" => $color);
		return $this;
	}
	
	/**
	 * removes a bubble
	 *
	 *@name removeBubble
	 *@access public
	 *@param text
	*/
	public function removeBubble($text) {
		unset($this->bubbles[md5($text)]);
		return $this;
	}
	
	/**
	 * returns all bubbles
	 *
	 *@name Bubbles
	 *@access public
	 *@param text
	*/
	public function bubbles() {
		return $this->bubbles;
	}
	
	/**
	 * sets children
	 *
	 *@name setChildren
	 *@access public
	*/
	public function setChildren($children) {
		// validate and stack it in
		$this->items = array();
		foreach($children as $child) {
			$this->push($child);
		}
	}
	
	/**
	 * sets lazy-loading-child-callback.
	 *
	 * @param 	callback $callback
	*/
	public function setChildCallback($callback, $params = array()) {
		if(is_callable($callback)) {
			$this->childCallback = $callback;
			$this->childParams = $params;
		} else
			throw new LogicException("TreeNode::setChildCallback: first argument must be a valid callback.");
	}
	
	/**
	 * returns current child-callback.
	*/
	public function getChildCallback() {
		return $this->childCallback;
	}
	
	/**
	 * adds a child
	 *
	 *@name addChild
	 *@access public
	*/
	public function addChild(TreeNode $child) {
		if($this->childCallback) {
			if(!isset($this->items))
				$this->items = array();
			
			$this->push($child);
		} else {
			throw new LogicException("This is a lazy loading TreeNode, you cannot add a child.");
		}
	}
	
	/**
	 * removes a child
	 *
	 *@name removeChild
	 *@access public
	*/
	public function removeChild(TreeNode $child) {
		if(is_array($this->items)) {
			$this->remove($child);
		} else {
			throw new LogicException("This is a lazy loading TreeNode, you cannot remove a child.");
		}
	}
	
	/**
	 * gets all children as ArrayList.
	 *
	 * @return	Array
	*/
	public function Children() {
		return $this->items;
	}
	
	/**
	 * gets all children as Array.
	*/
	public function getChildren() {
		return $this->children();
	}
	
	/**
	 * forces to get children
	 *
	 *@name forceChildren
	*/ 
	public function forceChildren() {
		if($this->childCallback) {
			if(isset($this->items)) {
				return $this->children();
			} else {
				$this->items = call_user_func_array($this->childCallback, array($this, (array) $this->childParams));
				return $this->children();
			}
		} else {
			return $this->Children();
		}
	}
	
	/**
	 * sets children collapsed
	 *
	 *@name setCollapsed
	 *@access public
	*/
	public function setCollapsed() {
		$this->childState = "collapsed";
	}
	
	/**
	 * returns if is Collapsed
	 *
	 *@name isCollaped
	 *@access public
	*/
	public function isCollapsed() {
		return ($this->childState == "collapsed");
	}
	
	/**
	 * sets children expanded
	 *
	 *@name setCollapsed
	 *@access public
	*/
	public function setExpanded() {
		$this->childState = "expanded";
	}
	
	/**
	 * returns if is Expanded
	 *
	 *@name isExpanded
	 *@access public
	*/
	public function isExpanded() {
		return ($this->childState == "expanded");
	}
	
	/**
	 * sets children to cookie-based
	 *
	 *@name setCookieBased
	 *@access public
	*/
	public function setCookieBased() {
		$this->childState = null;
	}
	
	/**
	 * returns the record
	 *
	 *@name record
	*/
	public function record() {
		if(isset($this->model))
			return $this->model;
		
		$this->model = DataObject::Get_by_id($this->treeclass, $this->recordid);
		return $this->model;
	}
	
	/**
	 * adds a html-class
	 *
	 *@name addClass
	 *@access public
	*/
	public function addClass($class) {
		$this->htmlClasses[$class] = $class;
	}
	
	/**
	 * rempves a html-class
	 *
	 *@name removeClass
	 *@access public
	*/
	public function removeClass($class) {
		unset($this->htmlClasses[$class]);
	}
	
	/**
	 * returns the HTML-Classes 
	 *
	 *@name getClasses
	 *@access public
	*/
	public function getClasses() {
		return $this->htmlClasses;
	}
	
	/**
	 * sets the linkCallback, which should generate the link of the tree-item.
	*/
	public function setLinkCallback($callback) {
		$this->linkCallback = $callback;
		return $this;
	}
	
	/**
	 * returns the current linkCallback.
	*/
	public function LinkCallback() {
		return $this->linkCallback;
	}
}